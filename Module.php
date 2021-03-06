<?php

namespace mamadali\webhook;

use mamadali\webhook\models\Webhook;
use mamadali\webhook\models\WebhookLog;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\httpclient\Response;
use yii\web\HttpException;

/**
 * To use Webhook, insert the following code to your common config file:
 * ```php
 * 'modules' => [
 *        // .... other modules
 *         "webhook" => [
 *             'class' => 'mamadali\webhook\Module',
 *             'url' => 'https://example.com/webhook' // your webhook url, not required if you set the url in the behavior config
 *         ],
 *        // .... other modules
 * ]
 * ```
 */
class Module extends \yii\base\Module
{

	/**
	 * @var string the webhook url.
	 */
	public $url;

	/**
	 * @var bool whether to enable the auth.
	 */
	public $auth = false;

	/**
	 * @var string the webhook url auth method
	 * you can use 'Basic' | 'Bearer' | 'Digest' | 'custom'
	 */
	public $authMethod = WebhookBehavior::AUTH_METHOD_BASIC;

	/**
	 * @var string the wat to send auth token
	 * you can use 'header' | 'body' | 'query'
	 */
	public $authTokenSendIn = WebhookBehavior::AUTH_TOKEN_SEND_IN_HEADER;


	/**
	 * @var string the auth token field name send in $authTokenSendIn
	 */
	public $authTokenField = 'authorization';

	/**
	 * @var string the webhook url auth toekn
	 * @see https://www.loginradius.com/blog/async/everything-you-want-to-know-about-authorization-headers/
	 * example for Basic: base64_encode("$username:$password");
	 * for other way just use the token
	 */
	public $authToken;

	/**
	 * @var string the http method.
	 */
	public $send_method = WebhookBehavior::HTTP_METHOD_POST;

	/**
	 * @var string the queue name
	 */
	public $queueName = 'queue';

	/**
	 * @var string additional data to send to job
	 */
	public $additionalJobData = [];

	/**
	 * @var string jobs namespace
	 */
	public $jobNamespace = 'mamadali\webhook\job';

	/**
	 * @var int the number of seconds to retry job when failed.
	 */
	public $jobTtr = 60;

	/**
	 * @var int the number of seconds to wait before run job.
	 */
	public $jobDelay = 0;

	/**
	 * @var bool save webhook log
	 */
	public $saveLog = true;

	public $controllerNamespace = 'mamadali\webhook\controllers';

	/**
	 * @var array
	 */
	public $modelMap = [];

	/**
	 * @var string
	 */
	public $webhookModelClass = 'mamadali\webhook\models\Webhook';

	/**
	 * @var string
	 */
	public $webhookLogModelClass = 'mamadali\webhook\models\WebhookLog';

	public function init()
	{
		parent::init();

		if($this->modelMap){
			if(isset($this->modelMap['Webhook'])){
				$this->webhookModelClass = $this->modelMap['Webhook'];
			}

			if (isset($this->modelMap['WebhookLog'])) {
				$this->webhookLogModelClass = $this->modelMap['WebhookLog'];
			}
		}

		if (!in_array($this->authMethod, WebhookBehavior::items('authMethods'))) {
			throw new InvalidConfigException('Invalid auth method');
		}

		if (!in_array($this->authTokenSendIn, WebhookBehavior::items('authTokenSendIn'))) {
			throw new InvalidConfigException('Invalid auth token send in');
		}


		if ($this->auth && !$this->authToken) {
			throw new InvalidConfigException('You must set authToken to enable auth.');
		}

		if (!in_array($this->send_method, WebhookBehavior::items('httpMethods'))) {
			throw new InvalidConfigException('Invalid send_method, must be GET, POST, PUT, DELETE or PATCH');
		}
	}

	/**
	 * @param $webhook_id integer the webhook id
	 *
	 * @return bool
	 */
	public function send($webhook_id)
	{
		$webhook = $this->findWebhookModel($webhook_id);

		$client = new Client();
		/** @var Response $response */
		$response = $client->createRequest()
			->setMethod($webhook->method)
			->setUrl($webhook->url)
			->setData(Json::decode($webhook->data))
			->setHeaders(Json::decode($webhook->headers))
			->send();

		$this->saveLog($webhook, $response);

		if ($response->isOk) {
			return true;
		} else {
			throw new HttpException($response->statusCode, json_encode($response->content));
		}
	}


	/**
	 * @param $webhook Webhook
	 * @param $response Response
	 */
	protected function saveLog($webhook, Response $response)
	{
		$transaction = \Yii::$app->db->beginTransaction();
		try {
			$log = new $this->webhookLogModelClass();
			$log->webhook_id = $webhook->id;
			$log->is_ok = $response->isOk;
			$log->response_status_code = $response->statusCode;
			$log->response_data = Json::encode($response->content);
			$log->response_headers = Json::encode($response->headers);

			if ($log->save()) {
				$transaction->commit();
				return $log;
			} else {
				$transaction->rollBack();
				throw new Exception(Html::errorSummary($log));
			}

		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * @param $id integer the webhook id
	 *
	 * @return Webhook
	 * @throws HttpException
	 */
	protected function findWebhookModel($id)
	{
		$model = $this->webhookModelClass::findOne($id);
		if (!$model) {
			throw new HttpException(404, 'Webhook not found');
		}
		return $model;
	}
}
