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

	const AUTH_METHOD_BASIC = 'Basic';
	const AUTH_METHOD_BEARER = 'Bearer';
	const AUTH_METHOD_DIGEST = 'Digest';
	const AUTH_METHOD_CUSTOM = 'custom';
	
	const HTTP_METHOD_GET = 'GET';
	const HTTP_METHOD_POST = 'POST';
	const HTTP_METHOD_PUT = 'PUT';
	const HTTP_METHOD_DELETE = 'DELETE';
	const HTTP_METHOD_PATCH = 'PATCH';
	
	const AUTH_TOKEN_SEND_IN_HEADER = 'header';
	const AUTH_TOKEN_SEND_IN_QUERY = 'query';
	const AUTH_TOKEN_SEND_IN_BODY = 'body';

	/**
	 * @var string the webhook url.
	 */
	public $url;

	/**
	 * @var bool whether to enable the auth.
	 */
	public $auth;

	/**
	 * @var string the webhook url auth method
	 * you can use 'Basic' | 'Bearer' | 'Digest' | 'custom'
	 */
	public $authMethod;

	/**
	 * @var string the wat to send auth token
	 * you can use 'header' | 'body' | 'query'
	 */
	public $authTokenSendIn;


	/**
	 * @var string the auth token field name send in $authTokenSendIn
	 */
	public $authTokenField;

	/**
	 * @var string the webhook url auth toekn
	 * @see https://www.loginradius.com/blog/async/everything-you-want-to-know-about-authorization-headers/
	 * example for Basic: base64_encode("$username:$password");
	 */
	public $authToken;

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
	 * @var string event name to send to webhook for after insert
	 */
	public $insertEventName = 'insert';

	/**
	 * @var string event name to send to webhook for after update
	 */
	public $updateEventName = 'update';

	/**
	 * @var string event name to send to webhook for after Delete
	 */
	public $deleteEventName = 'delete';

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

		if (!$this->auth) {
			$this->auth = $this->module->auth;
		}

		if (!$this->authMethod) {
			$this->authMethod = $this->module->authMethod;
		}

		if (!$this->authToken) {
			$this->authToken = $this->module->authToken;
		}

		if (!$this->authTokenSendIn) {
			$this->authTokenSendIn = $this->module->authTokenSendIn;
		}

		if (!$this->authTokenField) {
			$this->authTokenField = $this->module->authTokenField;
		}

		if (!in_array($this->authMethod, self::items('authMethods'))) {
			throw new InvalidConfigException('Invalid auth method');
		}

		if (!in_array($this->authTokenSendIn, self::items('authTokenSendIn'))) {
			throw new InvalidConfigException('Invalid auth token send in');
		}

		if($this->authToken && !$this->auth){
			throw new InvalidConfigException('You must set auth to true if you want to set authToken.');
		}

		if($this->auth && !$this->authToken) {
			throw new InvalidConfigException('You must set authToken to enable auth.');
		}

		if(!in_array($this->send_method, self::items('httpMethods'))) {
			throw new InvalidConfigException('Invalid send_method, must be GET, POST, PUT, DELETE or PATCH');
		}

		if (empty($this->url)) {
			throw new InvalidConfigException('The "url" property must be set.');
		}

		if (empty($this->modelName)) {
			throw new InvalidConfigException('The "modelName" property must be set.');
		}

		if($this->sendToQueue) {
			if (!Yii::$app->hasModule('webhook')) {
				throw new InvalidConfigException("You must configure 'webhook' module to use sendToQueue.");
			}

			if(!$this->module->queueName) {
				throw new InvalidConfigException("You must configure 'queueName' in 'webhook' module to use sendToQueue.");
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
			BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
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
            $this->sendToWebhook($this->insertEventName);
        }
	}

	/**
	 * This method is called at the beginning of updating a record.
	 */
    public function afterUpdate()
    {
        if($this->canSendWebhook()){
            $this->sendToWebhook($this->updateEventName);
        }
    }

	/**
	 * This method is called at the beginning of deleting a record.
	 */
    public function afterDelete()
    {
        if($this->canSendWebhook()){
            $this->sendToWebhook($this->deleteEventName);
        }
    }

    /**
     * @return bool
     * check if can send webhook
     */
    protected function canSendWebhook()
    {
        if($this->scenarios && !in_array($this->owner->scenario, $this->scenarios)){
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
			$webhook->url = $this->getUrl();
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
			] + $this->initializeData($this->module->additionalJobData)
		));
	}

	protected function getUrl()
	{
		$url = $this->url;
		if ($url instanceof Closure) {
			$url = call_user_func($url, $this->owner);
		}

		if ($this->auth && $this->authMethod == self::AUTH_METHOD_CUSTOM && $this->authTokenSendIn == self::AUTH_TOKEN_SEND_IN_QUERY) {
			$url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . http_build_query([
					$this->authTokenField => $this->authToken
				]);
		}
		return $url;
	}

	/**
	 * @return array
	 * get header paramerters
	 * when set auth is true set authorization method in header
	 */
	protected function getHeaders()
	{

		$auth = [];
		if($this->auth && $this->authTokenSendIn == self::AUTH_TOKEN_SEND_IN_HEADER){
			if ($this->authMethod == self::AUTH_METHOD_CUSTOM) {
				$auth = [$this->authTokenField => $this->authToken];
			} else {
				$auth = ['Authorization' => $this->authMethod . ' ' . $this->authToken];
			}
		}

		return $auth + $this->initializeData($this->headers);
	}

    /**
     * @param $action string event name
     * @return array data to send to webhook
     */
    protected function getResponse($action): array
    {
		$auth = [];
		if($this->auth && $this->authMethod == self::AUTH_METHOD_CUSTOM && $this->authTokenSendIn == self::AUTH_TOKEN_SEND_IN_BODY){
			$auth = [
				$this->authTokenField => $this->authToken
			];
		}

		if($this->primaryKey instanceof Closure) {
			$model_id = call_user_func($this->primaryKey, $this->owner);
		} else {
			$model_id = $this->owner->{$this->primaryKey};
		}

        return $auth + [
            'model_name' => $this->modelName,
            'action' => $action,
            'model_id' => $model_id,
            'data' => $this->getAttributes(),
        ] + $this->initializeData($this->additionalData);
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

            if(!in_array($field, $this->exceptAttributes)){
				if ($definition instanceof Closure) {
					$data[$field] = call_user_func($definition, $this->owner);
				} else {
					$data[$field] = $this->owner->$field;
				}
            }
        }

        return $data;
	}

	/**
	 * @param $array array data
	 * @return array
	 */
	protected function initializeData($array)
	{
		$data = [];

		foreach ($array as $field => $definition) {
			if ($definition instanceof Closure) {
				$data[$field] = call_user_func($definition, $this->owner);
			} else {
				$data[$field] = $definition;
			}
		}

		return $data;
	}

	public static function items($key)
	{
		$items = [
			'authMethods' => [
				self::AUTH_METHOD_BASIC,
				self::AUTH_METHOD_BEARER,
				self::AUTH_METHOD_DIGEST,
				self::AUTH_METHOD_CUSTOM,
			],
			'authTokenSendIn' => [
				self::AUTH_TOKEN_SEND_IN_HEADER,
				self::AUTH_TOKEN_SEND_IN_QUERY,
				self::AUTH_TOKEN_SEND_IN_BODY,
			],
			'httpMethods' => [
				self::HTTP_METHOD_GET,
				self::HTTP_METHOD_POST,
				self::HTTP_METHOD_PUT,
				self::HTTP_METHOD_DELETE,
				self::HTTP_METHOD_PATCH,
			],
		];

		return $items[$key] ?? [];
	}

}
