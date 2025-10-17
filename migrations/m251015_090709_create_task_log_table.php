<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_log}}`.
 */
class m251015_090709_create_task_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_log}}', [
            'id'         => $this->primaryKey(),
            'task_id'    => $this->integer()->notNull(),
            'user_id'    => $this->integer(),
            'operation'  => $this->string(32)->notNull(),
            'changes'    => $this->json()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey('fk_tasklog_task', '{{%task_log}}', 'task_id', '{{%task}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_tasklog_user', '{{%task_log}}', 'user_id', '{{%user}}', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_tasklog_task', '{{%task_log}}');
        $this->dropForeignKey('fk_tasklog_user', '{{%task_log}}');
        $this->dropTable('{{%task_log}}');
    }
}
