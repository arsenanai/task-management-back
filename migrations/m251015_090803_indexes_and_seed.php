<?php

use yii\db\Migration;

class m251015_090803_indexes_and_seed extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex('idx_task_status', '{{%task}}', 'status');
        $this->createIndex('idx_task_priority', '{{%task}}', 'priority');
        $this->createIndex('idx_task_due_date', '{{%task}}', 'due_date');
        $this->createIndex('idx_task_assigned', '{{%task}}', 'assigned_to');
        // seed admin user
        $time = time();
        $this->insert('{{%user}}', [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password_hash' => Yii::$app->getSecurity()->generatePasswordHash('password'),
            'role' => 'admin',
            'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%user}}', ['email' => 'admin@example.com']);
        $this->dropIndex('idx_task_status', '{{%task}}');
        $this->dropIndex('idx_task_priority', '{{%task}}');
        $this->dropIndex('idx_task_due_date', '{{%task}}');
        $this->dropIndex('idx_task_assigned', '{{%task}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251015_090803_indexes_and_seed cannot be reverted.\n";

        return false;
    }
    */
}
