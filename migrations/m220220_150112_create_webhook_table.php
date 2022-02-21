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
            'url' => $this->string()->notNull(),
            'method' => $this->string()->notNull(),
            'action' => $this->string()->notNull(),
            'model_name' => $this->string()->notNull(),
            'model_class' => $this->string()->notNull(),
            'model_id' => $this->integer()->notNull(),
            'data' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext'),
            'headers' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext'),
			'created_at' => $this->integer()->notNull(),
			'updated_at' => $this->integer()->notNull(),
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
