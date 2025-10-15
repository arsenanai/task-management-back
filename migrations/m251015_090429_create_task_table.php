<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task}}`.
 */
class m251015_090429_create_task_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'status' => "ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending'",
            'priority' => "ENUM('low','medium','high') NOT NULL DEFAULT 'medium'",
            'due_date' => $this->date(),
            'assigned_to' => $this->integer()->null(),
            'metadata' => $this->json(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'deleted_at' => $this->integer()->null(),
            'version' => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->addForeignKey('fk_task_user', '{{%task}}', 'assigned_to', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_task_user', '{{%task}}');
        $this->dropTable('{{%task}}');
    }
}
