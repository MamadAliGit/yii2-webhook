<?php

namespace mamadali\webhook;

use Closure;
use console\job\RepairJob;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

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
 *             'url' => 'https://example.com/webhook',
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
	 * @var Webhook
	 */
    private $webhookComponent;

	/**
	 * @var array of additional data to send to webhook
	 */
	public $additionalData = [];

	/**
	 * @var array of headers to send to webhook
	 */
	public $headers = [];


    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
	public function init()
	{
		parent::init();

        $this->webhookComponent = Yii::$app->get('webhook');

        if(!$this->url) {
            $this->url = $this->webhookComponent->url;
        }

		if(!$this->send_method) {
			$this->send_method = $this->webhookComponent->send_method;
		}

		if (empty($this->url)) {
			throw new InvalidConfigException('The "url" property must be set.');
		}

		if($this->sendToQueue) {
            if(!Yii::$app->has('webhook')){
                throw new InvalidConfigException("You must configure 'webhook' component to use sendToQueue.");
            }

            $queueName = $this->webhookComponent->queueName;
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

        if ($this->when instanceof Closure) {
            return (bool)call_user_func($this->when, $this->owner);
        }

        return true;
    }

    /**
     * @param $action string event name
     */
    protected function sendToWebhook(string $action)
    {
        $data = $this->getResponse($action);
		$headers = $this->getHeaders();

        if($this->sendToQueue) {
            $this->sendToQueue($this->url, $this->send_method, $data, $headers);
        } else {
            $this->webhookComponent->send($this->url, $this->send_method, $data, $headers);
        }
    }

	/**
	 * @param $url string url to send webhook
	 * @param $method string http method
	 * @param $data array of data to send to webhook
	 * @param $headers array of headers to send to webhook
	 * @return string job id
	 */
	protected function sendToQueue($url, $method, $data, $headers)
	{
		$jobClass = $this->webhookComponent->jobClass;
		return Yii::$app->{$this->webhookComponent->queueName}->push(new $jobClass([
				'url' => $url,
				'method' => $method,
				'data' => $data,
				'headers' => $headers,
			] + $this->webhookComponent->additionalJobData
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
