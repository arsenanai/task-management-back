<?php

use yii\db\Migration;
use app\models\User;
use app\models\Tag;
use app\models\Task;

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
        $tasks = [
            [
                'title' => 'Implement user authentication API',
                'description' => 'Develop the backend endpoints for user login and registration.',
                'status' => Task::STATUS_COMPLETED,
                'priority' => Task::PRIORITY_HIGH,
                'assigned_to' => $adminUser->id,
                'due_date' => date('Y-m-d', strtotime('-5 days')),
                'tags' => [$tagIds[0], $tagIds[4]] // Backend, Urgent
            ],
            [
                'title' => 'Design the new dashboard UI',
                'description' => 'Create mockups and wireframes for the new frontend dashboard.',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_MEDIUM,
                'assigned_to' => $regularUser->id,
                'due_date' => date('Y-m-d', strtotime('+10 days')),
                'tags' => [$tagIds[1]] // Frontend
            ],
            [
                'title' => 'Fix database connection timeout issue',
                'description' => 'The database connection occasionally times out under heavy load.',
                'status' => Task::STATUS_PENDING,
                'priority' => Task::PRIORITY_HIGH,
                'assigned_to' => $adminUser->id,
                'due_date' => date('Y-m-d', strtotime('+2 days')),
                'tags' => [$tagIds[2], $tagIds[5]] // Bug, Database
            ],
            [
                'title' => 'Add export-to-CSV feature',
                'description' => 'Users want to be able to export their task list as a CSV file.',
                'status' => Task::STATUS_PENDING,
                'priority' => Task::PRIORITY_LOW,
                'assigned_to' => null, // Unassigned
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'tags' => [$tagIds[3]] // Feature Request
            ],
            [
                'title' => 'Refactor the API response formatter',
                'description' => 'Improve the structure of all API responses for consistency.',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_MEDIUM,
                'assigned_to' => $adminUser->id,
                'due_date' => date('Y-m-d', strtotime('+7 days')),
                'tags' => [$tagIds[0]] // Backend
            ],
        ];

        foreach ($tasks as $taskData) {
            $task = new Task();
            $task->title = $taskData['title'];
            $task->description = $taskData['description'];
            $task->status = $taskData['status'];
            $task->priority = $taskData['priority'];
            $task->assigned_to = $taskData['assigned_to'];
            $task->due_date = $taskData['due_date'];
            $task->save(false);
            // Link tags
            $task->linkTags($taskData['tags']);
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
