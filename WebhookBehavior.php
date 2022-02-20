<?php

namespace mamadali\webhook;

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
	 * @var string|array the webhook url.
	 */
	public $url;

	/**
	 * @var string name of model class that will send to webhook
	 */
	public $modelName;

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
	 * @var bool save webhook log
	 */
	public $saveLog = true;

	/**
	 * @var bool send webhook data to queue and send with job
	 */
	public $sendToQueue = false;

	/**
	 * @var string the attribute that will send to webhook
	 */
	public $dataAttribute = 'data';


	public function init()
	{
		parent::init();
		if (empty($this->url)) {
			throw new InvalidConfigException('The "url" property must be set.');
		}

		if($this->sendToQueue && !Yii::$app->has('webhook')) {
			throw new InvalidConfigException("You must configure 'webhook' component to use sendToQueue.");
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

	}

	public function getData()
	{

	}

}
