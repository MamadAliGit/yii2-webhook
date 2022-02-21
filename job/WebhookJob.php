<?php

namespace mamadali\webhook\job;

use mamadali\webhook\Module;
use Yii;
use yii\base\BaseObject;
use yii\queue\RetryableJobInterface;

class WebhookJob extends BaseObject implements RetryableJobInterface
{
	/**
	 * @var integer the webhook id
	 */
	public $webhook_id;

	public function execute($queue)
	{
		/**
		 * @var Module
		 */
		$module = Yii::$app->getModule('webhook');
		$module->send($this->webhook_id);
	}


	public function getTtr()
	{
		/**
		 * @var Module
		 */
		$module = Yii::$app->getModule('webhook');
		return $module->jobTtr;
	}

	public function canRetry($attempt, $error)
	{
		return $attempt < 5;
	}
}
