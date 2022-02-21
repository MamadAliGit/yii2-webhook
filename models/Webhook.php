<?php

namespace mamadali\webhook\models;

use Yii;

/**
 * This is the model class for table "{{%webhook}}".
 *
 * @property int $id
 * @property string $model_name
 * @property string $model_class
 * @property int $model_id
 * @property array $data
 * @property string $headers
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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['model_name', 'model_class', 'model_id'], 'required'],
            [['model_id'], 'integer'],
            [['data'], 'safe'],
            [['model_name', 'model_class', 'headers'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
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
