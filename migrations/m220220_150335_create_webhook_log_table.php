<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%webhook_log}}`.
 */
class m220220_150335_create_webhook_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%webhook_log}}', [
            'id' => $this->primaryKey(),
            'webhook_id' => $this->integer()->notNull(),
            'is_ok' => $this->boolean(),
            'response_status_code' => $this->integer()->null(),
            'response_data' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext'),
            'response_headers' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext'),
			'created_at' => $this->integer()->notNull(),
			'updated_at' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%webhook_log}}');
    }
}
