<?php

namespace mamadali\webhook\models;

use Yii;

/**
 * This is the model class for table "{{%webhook_log}}".
 *
 * @property int $id
 * @property int $webhook_id
 * @property int $is_ok
 * @property int $response_status_code
 * @property string $response_data
 * @property string $response_headers
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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['webhook_id'], 'required'],
            [['webhook_id', 'is_ok', 'response_status_code'], 'integer'],
            [['response_data', 'response_headers'], 'string', 'max' => 255],
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