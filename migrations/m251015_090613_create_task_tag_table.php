<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_tag}}`.
 */
class m251015_090613_create_task_tag_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_tag}}', [
            'task_id' => $this->integer()->notNull(),
            'tag_id'  => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('pk_task_tag', '{{%task_tag}}', ['task_id', 'tag_id']);
        $this->addForeignKey('fk_tasktag_task', '{{%task_tag}}', 'task_id', '{{%task}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_tasktag_tag', '{{%task_tag}}', 'tag_id', '{{%tag}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_tasktag_task', '{{%task_tag}}');
        $this->dropForeignKey('fk_tasktag_tag', '{{%task_tag}}');
        $this->dropTable('{{%task_tag}}');
    }
}
