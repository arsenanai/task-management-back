<?php

namespace tests\unit\services;

use app\models\Tag;
use app\models\Task;
use app\models\User;
use app\services\TaskService;
use Codeception\Test\Unit;
use Yii;
use yii\db\StaleObjectException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;

class TaskServiceTest extends Unit
{
    protected $tester;

    private ?TaskService $taskService = null;
    private ?User $admin = null;
    private ?User $user = null;
    private ?User $otherUser = null;

    public function _before()
    {
        // Clean up and set up test data
        Task::deleteAll();
        Tag::deleteAll();
        User::deleteAll();

        $this->admin = new User(['name' => 'Admin', 'email' => 'admin@test.com', 'password_hash' => 'hash', 'role' => User::ROLE_ADMIN]);
        $this->admin->save(false);

        $this->user = new User(['name' => 'User', 'email' => 'user@test.com', 'password_hash' => 'hash', 'role' => User::ROLE_USER]);
        $this->user->save(false);

        $this->otherUser = new User(['name' => 'Other User', 'email' => 'other@test.com', 'password_hash' => 'hash', 'role' => User::ROLE_USER]);
        $this->otherUser->save(false);

        $this->taskService = new TaskService();
    }

    protected function loginAs(User $user)
    {
        Yii::$app->user->setIdentity($user);
    }

    public function testCreateTask()
    {
        $this->loginAs($this->user);
        $data = [
            'title' => 'A new task to be created',
            'due_date' => date('Y-m-d', strtotime('+2 days')),
        ];
        $task = $this->taskService->createTask($data);
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('A new task to be created', $task->title);
    }

    public function testCreateTaskFailsValidation()
    {
        $this->loginAs($this->user);
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->taskService->createTask(['title' => 'No']); // Title too short
    }

    public function testGetTaskById()
    {
        $this->loginAs($this->admin);
        $task = new Task(['title' => 'Find me', 'assigned_to' => $this->user->id]);
        $task->save(false);

        $foundTask = $this->taskService->getTaskById($task->id);
        $this->assertEquals($task->id, $foundTask->id);
    }

    public function testGetTaskByIdThrowsNotFound()
    {
        $this->loginAs($this->admin);
        $this->expectException(NotFoundHttpException::class);
        $this->taskService->getTaskById(99999);
    }

    public function testUpdateTask()
    {
        $this->loginAs($this->user);
        $task = new Task(['title' => 'Update me', 'assigned_to' => $this->user->id, 'version' => 0]);
        $task->save(false);
        $task->refresh();

        $updatedTask = $this->taskService->updateTask($task->id, [
            'title' => 'I have been updated',
            'version' => $task->version,
        ]);

        $this->assertEquals('I have been updated', $updatedTask->title);
        $this->assertEquals(1, $updatedTask->version);
    }

    public function testUpdateTaskThrowsConflictOnStaleVersion()
    {
        $this->loginAs($this->user);
        // 1. Create an original task
        $task1 = new Task(['title' => 'Original Task', 'assigned_to' => $this->user->id]);
        $task1->save(false);

        // 2. Create two separate instances from the same database state
        $instanceForUser1 = Task::findOne($task1->id);
        $instanceForUser2 = Task::findOne($task1->id);

        // 3. The first user successfully updates the task, bumping the version in the DB to 1
        $instanceForUser1->title = 'First update';
        $instanceForUser1->save(false);

        $this->expectException(ConflictHttpException::class);
        // 4. The second user now tries to update using their stale instance (which still has version 0)
        $this->taskService->updateTask($instanceForUser2->id, [
            'title' => 'This update should fail',
            'version' => $instanceForUser2->version, // This is the stale version 0
        ]);
    }

    public function testDeleteAndRestoreTask()
    {
        $this->loginAs($this->user);
        $task = new Task(['title' => 'Delete me', 'assigned_to' => $this->user->id]);
        $task->save(false);

        // Delete
        $result = $this->taskService->deleteTask($task->id);
        $this->assertTrue($result);
        $this->assertNull(Task::findOne($task->id), 'Task should be soft-deleted.');

        // Restore
        $restoredTask = $this->taskService->restoreTask($task->id);
        $this->assertNotNull($restoredTask);
        $this->assertNull($restoredTask->deleted_at);
    }

    public function testToggleStatus()
    {
        $this->loginAs($this->user);
        $task = new Task(['title' => 'Toggle status', 'assigned_to' => $this->user->id, 'status' => Task::STATUS_PENDING]);
        $task->save(false);

        // Pending -> In Progress
        $toggledTask = $this->taskService->toggleTaskStatus($task->id);
        $this->assertEquals(Task::STATUS_IN_PROGRESS, $toggledTask->status);

        // In Progress -> Completed
        $toggledTask = $this->taskService->toggleTaskStatus($task->id);
        $this->assertEquals(Task::STATUS_COMPLETED, $toggledTask->status);
    }

    public function testSearchByStatus()
    {
        $this->loginAs($this->admin);
        (new Task(['title' => 'Pending Task', 'status' => Task::STATUS_PENDING]))->save(false);
        (new Task(['title' => 'Completed Task', 'status' => Task::STATUS_COMPLETED]))->save(false);

        $dataProvider = $this->taskService->search(['status' => Task::STATUS_PENDING]);
        $this->assertEquals(1, $dataProvider->getTotalCount());
        $this->assertEquals('Pending Task', $dataProvider->getModels()[0]->title);
    }

    public function testSearchByKeyword()
    {
        $this->loginAs($this->admin);
        (new Task(['title' => 'First task with keyword apple']))->save(false);
        (new Task(['title' => 'Second task', 'description' => 'Description contains apple']))->save(false);
        (new Task(['title' => 'Third task without it']))->save(false);

        $dataProvider = $this->taskService->search(['keyword' => 'apple']);
        $this->assertEquals(2, $dataProvider->getTotalCount());
    }

    public function testSearchByTags()
    {
        $this->loginAs($this->admin);
        $tag1 = new Tag(['name' => 'Urgent']);
        $tag1->save(false);
        $tag2 = new Tag(['name' => 'Bug']);
        $tag2->save(false);

        $task1 = new Task(['title' => 'Task with one tag']);
        $task1->save(false);
        $task1->link('tags', $tag1);

        $task2 = new Task(['title' => 'Task with two tags']);
        $task2->save(false);
        $task2->link('tags', $tag1);
        $task2->link('tags', $tag2);

        // Search for one tag
        $dataProvider = $this->taskService->search(['tags' => $tag1->id]);
        $this->assertEquals(2, $dataProvider->getTotalCount());

        // Search for two tags (AND logic)
        $dataProvider = $this->taskService->search(['tags' => "{$tag1->id},{$tag2->id}"]);
        $this->assertEquals(1, $dataProvider->getTotalCount());
        $this->assertEquals('Task with two tags', $dataProvider->getModels()[0]->title);
    }

    public function testSearchByPriorityAndAssignedTo()
    {
        $this->loginAs($this->admin);
        (new Task(['title' => 'High Prio for Admin', 'priority' => Task::PRIORITY_HIGH, 'assigned_to' => $this->admin->id]))->save(false);
        (new Task(['title' => 'Low Prio for Admin', 'priority' => Task::PRIORITY_LOW, 'assigned_to' => $this->admin->id]))->save(false);
        (new Task(['title' => 'High Prio for User', 'priority' => Task::PRIORITY_HIGH, 'assigned_to' => $this->user->id]))->save(false);

        // Test filter by priority
        $dataProvider = $this->taskService->search(['priority' => Task::PRIORITY_HIGH]);
        $this->assertEquals(2, $dataProvider->getTotalCount());

        // Test filter by assigned_to
        $dataProvider = $this->taskService->search(['assigned_to' => $this->admin->id]);
        $this->assertEquals(2, $dataProvider->getTotalCount());

        // Test combined filters
        $dataProvider = $this->taskService->search(['priority' => Task::PRIORITY_HIGH, 'assigned_to' => $this->admin->id]);
        $this->assertEquals(1, $dataProvider->getTotalCount());
        $this->assertEquals('High Prio for Admin', $dataProvider->getModels()[0]->title);
    }

    public function testSearchAndSort()
    {
        $this->loginAs($this->admin);
        (new Task(['title' => 'A - Second', 'created_at' => time() + 100]))->save(false);
        (new Task(['title' => 'B - First', 'created_at' => time()]))->save(false);

        // Default sort (created_at DESC)
        $dataProvider = $this->taskService->search([]);
        $this->assertEquals('A - Second', $dataProvider->getModels()[0]->title);

        // Sort by title ASC
        $dataProvider = $this->taskService->search(['sort' => 'title']);
        $this->assertEquals('A - Second', $dataProvider->getModels()[0]->title);

        // Sort by title DESC
        $dataProvider = $this->taskService->search(['sort' => '-title']);
        $this->assertEquals('B - First', $dataProvider->getModels()[0]->title);
    }

    public function testAccessControlForUser()
    {
        // User can't access other user's task
        $this->loginAs($this->otherUser);
        $task = new Task(['title' => 'Owned by main user', 'assigned_to' => $this->user->id]);
        $task->save(false);

        $this->expectException(ForbiddenHttpException::class);
        $this->taskService->getTaskById($task->id);
    }

    public function testAccessControlForAdmin()
    {
        // Admin can access other user's task
        $this->loginAs($this->admin);
        $task = new Task(['title' => 'Owned by main user', 'assigned_to' => $this->user->id]);
        $task->save(false);

        $foundTask = $this->taskService->getTaskById($task->id);
        $this->assertEquals($task->id, $foundTask->id);
    }
}
