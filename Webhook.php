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
     * @var string|array the webhook url.
     */
    public $url;

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
}