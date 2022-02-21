<?php

namespace mamadali\webhook;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\httpclient\Response;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnauthorizedHttpException;

/**
 * Webhook component
 *
 * @author MamadAli <mhmd.ali.shabani@gmail.com>
 */
class Webhook extends Component
{
	/**
	 * @var string|array the webhook url.
	 */
	public $url;

	/**
	 * @var string the http method.
	 */
	public $send_method = 'POST';

	/**
	 * @var string the queue name
	 */
	public $queueName = 'queue';

	/**
	 * @var string additional data to send to job
	 */
	public $additionalJobData = [];

	/**
	 * @var string job class name
	 */
	public $jobClass = 'mamadali\webhook\job\WebhookJob';

	/**
	 * @var bool save webhook log
	 */
	public $saveLog = true;

	public function init()
	{
		parent::init();
	}

	/**
	 * @param $url string the webhook url
	 * @param $data array the data to send
	 * @param $headers array the headers to send
	 *
	 * @return bool
	 */
	public function send($url, $method, $data, $headers)
	{
		$client = new Client();
		/** @var Response $response */
		$response = $client->createRequest()
			->setMethod($method)
			->setUrl($url)
			->setData($data)
			->setHeaders($headers)
			->send();

		$this->saveLog($url, $method, $data, $headers, $response);

		if ($response->isOk) {
			return true;
		} else {
			throw new HttpException($response->statusCode);
		}
	}

}