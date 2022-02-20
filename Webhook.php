<?php

namespace mamadali\webhook;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Webhook component
 *
 * @author MamadAli <mhmd.ali.shabani@gmail.com>
 */
class Webhook extends Component
{
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

	public function init()
	{
		parent::init();
		if (!Yii::$app->has($this->queueName)) {
			throw new InvalidConfigException("You must configure '$this->queueName' component to use webhook component.");
		}
	}
}