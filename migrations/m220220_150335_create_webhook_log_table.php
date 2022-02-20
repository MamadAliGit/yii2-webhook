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
            'model_name' => $this->string()->notNull(),
            'model_class' => $this->string()->notNull(),
            'model_id' => $this->integer()->notNull(),
            'data' => $this->json()->null()->defaultValue(null),
            'status' => $this->integer()->notNull()->defaultValue(1),
            'created_by' => $this->integer()->unsigned()->null()->defaultValue(null),
            'updated_by' => $this->integer()->unsigned()->null()->defaultValue(null),
            'created_at' => $this->integer()->unsigned()->null()->defaultValue(null),
            'updated_at' => $this->integer()->unsigned()->null()->defaultValue(null),
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
