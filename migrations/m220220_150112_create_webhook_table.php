<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%webhook_log}}`.
 */
class m220220_150112_create_webhook_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%webhook}}', [
            'id' => $this->primaryKey(),
            'model_name' => $this->string()->notNull(),
            'model_class' => $this->string()->notNull(),
            'model_id' => $this->integer()->notNull(),
            'data' => $this->json()->null(),
            'headers' => $this->string()->null(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%webhook}}');
    }
}
