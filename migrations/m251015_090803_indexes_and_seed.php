<?php

use yii\db\Migration;
use app\models\User;
use app\models\Tag;
use app\models\Task;
use Faker\Factory;

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
            'name'          => 'Admin User',
            'email'         => 'admin@example.com',
            'password_hash' => Yii::$app->getSecurity()->generatePasswordHash('password'),
            'role'          => 'admin',
            'auth_key'      => Yii::$app->getSecurity()->generateRandomString(),
            'created_at'    => $time,
            'updated_at'    => $time,
        ]);

        $this->insert('{{%user}}', [
            'name'          => 'Regular User',
            'email'         => 'user@example.com',
            'password_hash' => Yii::$app->getSecurity()->generatePasswordHash('userpass'),
            'role'          => 'user',
            'auth_key'      => Yii::$app->getSecurity()->generateRandomString(),
            'created_at'    => $time,
            'updated_at'    => $time,
        ]);

        // Get user IDs for task assignment
        $adminUser = User::findOne(['email' => 'admin@example.com']);
        $regularUser = User::findOne(['email' => 'user@example.com']);

        // Seed some tags
        $tags = ['Backend', 'Frontend', 'Bug', 'Feature Request', 'Urgent', 'Database'];
        foreach ($tags as $tagName) {
            $this->insert('{{%tag}}', ['name' => $tagName]);
        }
        $tagIds = Tag::find()->select('id')->column();

        // Seed diverse tasks
        $faker = Factory::create();
        $userIds = [$adminUser->id, $regularUser->id, null];
        $statuses = [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED];
        $priorities = [Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH];

        for ($i = 0; $i < 120; $i++) {
            $task = new Task();
            $task->title = $faker->sentence(6);
            $task->description = $faker->paragraph(3);
            $task->status = $statuses[array_rand($statuses)];
            $task->priority = $priorities[array_rand($priorities)];
            $task->assigned_to = $userIds[array_rand($userIds)];
            $task->due_date = $faker->dateTimeBetween('-30 days', '+60 days')->format('Y-m-d');
            $task->save(false);

            // Link tags
            $numTags = rand(0, 3);
            if ($numTags > 0) {
                $randomTagIds = [];
                $randKeys = array_rand($tagIds, $numTags);
                if (is_array($randKeys)) {
                    foreach ($randKeys as $key) {
                        $randomTagIds[] = $tagIds[$key];
                    }
                } else {
                    $randomTagIds[] = $tagIds[$randKeys];
                }
                $task->linkTags($randomTagIds);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%user}}', ['email' => 'admin@example.com']);
        $this->delete('{{%user}}', ['email' => 'user@example.com']);
        // Truncate task and tag related tables
        $this->truncateTable('{{%task_tag}}');
        $this->truncateTable('{{%task}}');
        $this->truncateTable('{{%tag}}');
        $this->dropIndex('idx_task_status', '{{%task}}');
        $this->dropIndex('idx_task_priority', '{{%task}}');
        $this->dropIndex('idx_task_due_date', '{{%task}}');
        $this->dropIndex('idx_task_assigned', '{{%task}}');
    }
}
