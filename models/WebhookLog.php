<?php

namespace mamadali\webhook\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%webhook_log}}".
 *
 * @property int $id
 * @property int $webhook_id
 * @property int $is_ok
 * @property int $response_status_code
 * @property string $response_data
 * @property string $response_headers
 * @property int $created_at
 * @property int $updated_at
 */
class WebhookLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%webhook_log}}';
    }

	public function behaviors()
	{
		return [
			TimestampBehavior::class,
		];
	}

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['webhook_id'], 'required'],
            [['webhook_id', 'response_status_code', 'created_at', 'updated_at'], 'integer'],
			[['is_ok'], 'boolean'],
            [['response_data', 'response_headers'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'webhook_id' => Yii::t('app', 'Webhook ID'),
            'is_ok' => Yii::t('app', 'Is Ok'),
            'response_status_code' => Yii::t('app', 'Response Status Code'),
            'response_data' => Yii::t('app', 'Response Data'),
            'response_headers' => Yii::t('app', 'Response Headers'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return WebhookLogQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new WebhookLogQuery(get_called_class());
    }
}
