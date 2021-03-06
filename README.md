Yii2 Webhook Behavior
=====================
Helps you to send models changes to a webhook url

[![Latest Stable Version](https://img.shields.io/packagist/v/mamadali/yii2-webhook.svg)](https://packagist.org/packages/mamadali/yii2-webhook)
[![Total Downloads](https://img.shields.io/packagist/dt/mamadali/yii2-webhook.svg)](https://packagist.org/packages/mamadali/yii2-webhook)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist mamadali/yii2-webhook "*"
```

or add

```
"mamadali/yii2-webhook": "*"
```

to the require section of your `composer.json` file.

then run migrations

```
php yii migrate/up --migrationPath=@vendor/mamadali/yii2-webhook/migrations
```

#Basic usage
first add to config.php or if use advanced project add to common/config/main.php

```php
    'modules' => [
        ...
        'webhook' => [
            'class' => 'mamadali\webhook\Module',
            'url' => 'https://example.com/webhook',
        ],
        ...
    ];
```

To send model change to webhook you need to add to your model
```php
public function behaviors()
{
    return [
		[
		    'class' => 'mamadali\webhook\WebhookBehavior',
			'modelName' => 'ExampleModel', // your model name, required. send model name in webhook data
		],
	];
}
```
When any changes on your model, will be send data to webhook like this:
```json
{
    "model_name": "ExampleModel",
    "action": "insert", // or update or delete
    "model_id": 4, // id of model
    "data": { // all data of your model
        "id": 4,
        "title": "Example title",
        "status": 1,
        "created_at": 1633153382,
        "updated_at": 1645517769
    }
}
```
#Advanced usage

### You can add authentication to your webhook url, like this:

for use basic auth:

```php
    'modules' => [
        ...
        'webhook' => [
            'class' => 'mamadali\webhook\Module',
            'url' => 'https://example.com/webhook',
            'authToken' => base64_encode("$username:$password"), // change username and password
        ],
        ...
    ];
```

for use bearer auth:

```php
    'modules' => [
        ...
        'webhook' => [
            'class' => 'mamadali\webhook\Module',
            'url' => 'https://example.com/webhook',
            'authMethod' => 'Bearer',
            'authToken' => $token, // your token here
        ],
        ...
    ];
```


###you can change url and auth for send webhook in any model, like this:
```php
public function behaviors()
{
	return [
		[
			'class' => 'mamadali\webhook\WebhookBehavior',
			'modelName' => 'ExampleModel', // your model name, required. send model name in webhook data
			'url' => 'https://example.com/webhook/example-model',
		],
	];
}
```

you can send data to webhook only on specific scenarios or except scenarios, like this:
```php
public function behaviors()
{
	return [
		[
			'class' => 'mamadali\webhook\WebhookBehavior',
			'modelName' => 'ExampleModel', // your model name, required. send model name in webhook data
			'scenarios' => ['insert', 'update'], // send data only on these scenarios
			'except' => ['delete'], // Send data except in these scenarios
		],
	];
}
```
you can customize attributes to send in webhook, like this:

```php
public function behaviors()
{
	return [
		[
		    'class' => 'mamadali\webhook\WebhookBehavior',
			'modelName' => 'ExampleModel', // your model name, required. send model name in webhook data
			'attributes' => [
				'title' => function (self $model) {
					return $model->getFullTitle();
				},
				'email',
				'created_at' => function (self $model) {
                    return date('Y-m-d H:i:s', $model->created_at);
                },
			],
		],
	];
}
```
you can set excepted attributes, if set, will be send all attributes except the ones in this array

```php
public function behaviors()
{
	return [
		[
		    'class' => 'mamadali\webhook\WebhookBehavior',
			'modelName' => 'ExampleModel', // your model name, required. send model name in webhook data
			'exceptAttributes' => [
				'status',
				'password'
			],
		],
	];
}
```
you can use 'when' property to send data only on when this function return true, like this:

```php
public function behaviors()
{
	return [
		[
		    'class' => 'mamadali\webhook\WebhookBehavior',
			'modelName' => 'ExampleModel', // your model name, required. send model name in webhook data
			'when' => function(self $model) {
                            return $model->status == 1;
			},
		],
	];
}
```

####you can send webhook with queue
to use queue you need first configure queue from [Yii2 Queue Document](https://github.com/yiisoft/yii2-queue/blob/master/docs/guide/README.md)

then you can use queue in your model, like this:

```php
public function behaviors()
{
	return [
		[
		    'class' => 'mamadali\webhook\WebhookBehavior',
			'modelName' => 'ExampleModel', // your model name, required. send model name in webhook data
			'sendToQueue' => true,
		],
	];
}
```

##Advanced usage queue
you can override job in your config

```php
    'modules' => [
        ...
        'webhook' => [
            'class' => 'mamadali\webhook\Module',
            'url' => 'https://example.com/webhook',
            'jobNamespace' => 'console\job',
        ],
        ...
    ];
```
and create WebhookJob in your namespace, like this:
```php
<?php

namespace console\job;

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
		return 60;
	}

	public function canRetry($attempt, $error)
	{
		return $attempt < 5;
	}
}
```

