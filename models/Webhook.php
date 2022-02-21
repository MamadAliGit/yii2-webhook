<?php

namespace mamadali\webhook\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%webhook}}".
 *
 * @property int $id
 * @property string $url
 * @property string $method
 * @property string $action
 * @property string $model_name
 * @property string $model_class
 * @property int $model_id
 * @property string $data
 * @property string $headers
 * @property int $created_at
 * @property int $updated_at
 */
class Webhook extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%webhook}}';
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
            [['url', 'method', 'action', 'model_name', 'model_class', 'model_id'], 'required'],
			//[['url'], 'url'],
            [['model_id', 'created_at', 'updated_at'], 'integer'],
            [['data', 'headers'], 'safe'],
            [['model_name', 'model_class', 'method', 'action'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'url' => Yii::t('app', 'Url'),
            'method' => Yii::t('app', 'Method'),
            'action' => Yii::t('app', 'Action'),
            'model_name' => Yii::t('app', 'Model Name'),
            'model_class' => Yii::t('app', 'Model Class'),
            'model_id' => Yii::t('app', 'Model ID'),
            'data' => Yii::t('app', 'Data'),
            'headers' => Yii::t('app', 'Headers'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return WebhookQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new WebhookQuery(get_called_class());
    }
}
