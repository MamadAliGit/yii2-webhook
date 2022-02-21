<?php

namespace mamadali\webhook;

use Closure;
use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\Html;
use mamadali\webhook\models\Webhook AS WebhookModel;
use yii\helpers\Json;

/**
 * WebhookBehavior automatically send your selected fields to webhook url
 * on any change in your selected scenarios.
 * To use WebhookBehavior, insert the following code to your ActiveRecord class:
 * ```php
 * function behaviors()
 * {
 *     return [
 *         [
 *             'class' => 'mamadali\webhook\WebhookBehavior',
 *             'url' => 'https://example.com/webhook', // your webhook url, not required if you set in config
 * 				'modelName' => 'User', // your model name, required. send model name in webhook data
 * 				// scenarios to send data to webhook. if not set, it will send data on every change
 *             'scenarios' => ['insert', 'update'],
 * 				// except scenarios
 * 				'except' => ['delete'],
 * 				// if not set attributes, will be send all model attributes when create or changes
 *             'attributes' => [
 *                    'name' => function(self $model) {
 *                        return $model->fullName;
 *                    },
 * 					'email',
 * 				],
 * 				// if set, will be send all attributes except the ones in this array
 * 				'exceptAttributes' => [
 * 					'password'
 * 				],
 * 				// when this function return true, it will send data to webhook
 * 				'when'	=> function(self $model) {
 * 					return $model->isNewRecord;
 * 				},
 *         ],
 *     ];
 * }
 * ```
 *
 * @author MamadAli <mhmd.ali.shabani@gmail.com>
 * @property ActiveRecord $owner
 */
class WebhookBehavior extends Behavior
{

	/**
	 * @var string the webhook url.
	 */
	public $url;

	/**
	 * @var string the http method.
	 */
	public $send_method;

	/**
	 * @var string name of model class that will send to webhook
	 */
	public $modelName;

    /**
     * @var string the primary key field name.
     */
    public $primaryKey = 'id';

	/**
	 * @var array the scenarios in which the behavior will be triggered
	 */
	public $scenarios = [];

	/**
	 * @var array the scenarios in which the behavior will be not triggered
	 */
	public $except = [];

	/**
	 * @var array the attributes to send to webhook
	 */
	public $attributes = [];

	/**
	 * @var array the attributes to not send to webhook
	 */
	public $exceptAttributes = [];

	/** @var
	 * @var callable a PHP callable that will be called when the event is triggered.
	 */
	public $when;

	/**
	 * @var bool send webhook data to queue and send with job
	 */
	public $sendToQueue = false;

	/**
	 * @var array of additional data to send to webhook
	 */
	public $additionalData = [];

	/**
	 * @var array of headers to send to webhook
	 */
	public $headers = [];

	/**
	 * @var Module
	 */
	public $module;


    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
	public function init()
	{
		parent::init();

		if(Yii::$app->hasModule('webhook')) {
			$this->module = Yii::$app->getModule('webhook');
		} else {
			throw new InvalidConfigException('You must configure "webhook" module first.');
		}

        if(!$this->url) {
            $this->url = $this->module->url;
        }

		if(!$this->send_method) {
			$this->send_method = $this->module->send_method;
		}

		if(!in_array($this->send_method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])) {
			throw new InvalidConfigException('Invalid send_method, must be GET, POST, PUT, DELETE or PATCH');
		}

		if (empty($this->url)) {
			throw new InvalidConfigException('The "url" property must be set.');
		}

		if (empty($this->modelName)) {
			throw new InvalidConfigException('The "modelName" property must be set.');
		}

		if($this->sendToQueue) {
            if(!Yii::$app->has('webhook')){
                throw new InvalidConfigException("You must configure 'webhook' component to use sendToQueue.");
            }

            $queueName = $this->module->queueName;
            if (!Yii::$app->has($queueName)) {
                throw new InvalidConfigException("You must configure '$queueName' component to use webhook component.");
            }
		}

        if($this->when && !is_callable($this->when)) {
            throw new InvalidConfigException('The "when" property must be callable.');
        }
	}

	public function events()
	{
		return [
			BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
			BaseActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
			BaseActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
		];
	}

	/**
	 * This method is called at the beginning of inserting a record.
	 */
	public function afterInsert()
	{
        if($this->canSendWebhook()){
            $this->sendToWebhook('insert');
        }
	}

    public function beforeUpdate()
    {
        if($this->canSendWebhook()){
            $this->sendToWebhook('update');
        }
    }

    public function afterDelete()
    {
        if($this->canSendWebhook()){
            $this->sendToWebhook('delete');
        }
    }

    /**
     * @return bool
     * check if can send webhook
     */
    protected function canSendWebhook()
    {
        if(!in_array($this->owner->scenario, $this->scenarios)){
            return false;
        }

        if ($this->when && $this->when instanceof Closure) {
            return (bool)call_user_func($this->when, $this->owner);
        }

        return true;
    }

    /**
     * @param $action string event name
     */
    protected function sendToWebhook(string $action)
    {
		$webhook = $this->saveWebhook($action);

        if($this->sendToQueue) {
            $this->sendToQueue($webhook->id);
        } else {
            $this->module->send($webhook->id);
        }
    }

	/**
	 * @param $action string event name
	 *
	 * @return models\Webhook
	 * @throws Exception
	 * @throws \yii\db\Exception
	 */
	protected function saveWebhook($action)
	{
		$transaction = \Yii::$app->db->beginTransaction();
		try {
			$webhook = new WebhookModel();
			$webhook->url = $this->url;
			$webhook->action = $action;
			$webhook->model_id = $this->owner->{$this->primaryKey};
			$webhook->model_name = $this->modelName;
			$webhook->model_class = get_class($this->owner);
			$webhook->method = $this->send_method;
			$webhook->data = Json::encode($this->getResponse($action));
			$webhook->headers = Json::encode($this->getHeaders());

			if ($webhook->save()) {
				$transaction->commit();
				return $webhook;
			} else {
				$transaction->rollBack();
				throw new Exception(Html::errorSummary($webhook));
			}

		} catch (\Exception $e) {
		    $transaction->rollBack();
		    throw $e;
		}
	}

	/**
	 * @param $webhook_id integer id of webhook
	 *
	 * @return string job id
	 */
	protected function sendToQueue($webhook_id)
	{
		$jobClass = $this->module->jobNamespace . '\WebhookJob';
		return Yii::$app->{$this->module->queueName}->push(new $jobClass([
				'webhook_id' => $webhook_id,
			] + $this->getJobAdditionalData()
		));
	}

    /**
     * @param $action string event name
     * @return array data to send to webhook
     */
    protected function getResponse($action): array
    {
        return [
            'model_name' => $this->modelName,
            'action' => $action,
            'model_id' => $this->owner->{$this->primaryKey},
            'data' => $this->getAttributes(),
        ] + $this->getAdditionalData();
    }

    /**
     * @return array attributes to send to webhook
     */
	protected function getAttributes(): array
    {
        $attributes = !empty($this->attributes) ? $this->attributes : $this->owner->getAttributes();
        $data = [];

        foreach ($attributes as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }

            if(is_string($definition) && !in_array($field, $this->exceptAttributes)){
                $data[$field] = $this->owner->$field;
            } elseif ($definition instanceof Closure) {
                $data[$field] = call_user_func($definition, $this->owner);
            }
        }

        return $data;
	}

	/**
	 * @return array
	 */
	protected function getAdditionalData()
	{
		$data = [];

		foreach ($this->additionalData as $field => $definition) {
			if ($definition instanceof Closure) {
				$data[$field] = call_user_func($definition, $this->owner);
			} else {
				$data[$field] = $definition;
			}
		}

		return $data;
	}

	/**
	 * @return array
	 */
	protected function getJobAdditionalData()
	{
		$data = [];

		foreach ($this->module->additionalJobData as $field => $definition) {
			if ($definition instanceof Closure) {
				$data[$field] = call_user_func($definition, $this->owner);
			} else {
				$data[$field] = $definition;
			}
		}

		return $data;
	}

	public function getHeaders()
	{
		$data = [];

		foreach ($this->headers as $field => $definition) {
			if ($definition instanceof Closure) {
				$data[$field] = call_user_func($definition, $this->owner);
			} else {
				$data[$field] = $definition;
			}
		}

		return $data;
	}

}
