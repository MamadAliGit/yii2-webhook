<?php

namespace mamadali\webhook\models;

/**
 * This is the ActiveQuery class for [[Webhook]].
 *
 * @see Webhook
 */
class WebhookQuery extends \yii\db\ActiveQuery
{

    /**
     * {@inheritdoc}
     * @return Webhook[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Webhook|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
