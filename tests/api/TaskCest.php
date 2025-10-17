<?php

namespace tests\api;

use Firebase\JWT\JWT;
use app\models\User;
use app\models\Task;
use Codeception\Util\Fixtures;
use DateTimeImmutable;
use Yii;

use function date;

/**
 * @property \ApiTester $tester
 */
class TaskCest
{
    public function _before(\ApiTester $I)
    {
        // Create or get a test user
        $user = User::findOne(['email' => 'test@example.com']);
        if (!$user) {
            $user = new User([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password_hash' => Yii::$app->security->generatePasswordHash('password'),
                'role' => 'user',
            ]);
            $user->save(false);
        }

        // Generate JWT token for this user
        $time = time();
        $key = Yii::$app->params['jwtSecret'];
        $payload = [
            'iss' => 'http://example.com',
            'aud' => 'http://example.org',
            'iat' => $time,
            'nbf' => $time,
            'exp' => $time + 3600,
            'uid' => $user->id,
            'role' => $user->role,
        ];
        $token = JWT::encode($payload, $key, 'HS256');

        $I->amBearerAuthenticated($token);
    }

    public function createTask(\ApiTester $I)
    {
        $I->sendPOST('/tasks', [
            'title' => 'Test Task',
            'due_date' => date('Y-m-d', strtotime('+1 day')),
            'status' => Task::STATUS_PENDING,
            'priority' => Task::PRIORITY_MEDIUM,
        ]);

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeResponseMatchesJsonType([
            'data' => [
                'id' => 'integer',
                'title' => 'string',
                'status' => 'string',
                'version' => 'integer',
            ]
        ]);
    }

    public function updateTaskWithOptimisticLock(\ApiTester $I)
    {
        $user = User::findOne(['email' => 'test@example.com']);
        $task = new Task([
            'title' => 'Task for optimistic lock test',
            'due_date' => date('Y-m-d'),
            'assigned_to' => $user->id,
        ]);
        $task->save(false);
        $task->refresh();
        $I->assertNotNull($task);

        // valid version
        $I->sendPUT("/tasks/{$task->id}", [
            'title' => 'Updated Task Title',
            'version' => $task->version,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true, 'data' => ['title' => 'Updated Task Title']]);

        // stale version
        $I->sendPUT("/tasks/{$task->id}", [
            'title' => 'Conflict Title',
            'version' => 0,
        ]);
        $I->seeResponseCodeIs(409);
        $I->seeResponseContainsJson(['success' => false, 'data' => ['name' => 'Conflict']]);
    }

    public function toggleStatus(\ApiTester $I)
    {
        $user = User::findOne(['email' => 'test@example.com']);
        $task = new Task([
            'title' => 'Task for toggle status test',
            'due_date' => date('Y-m-d'),
            'assigned_to' => $user->id,
        ]);
        $task->save(false);
        $I->sendPATCH("/tasks/{$task->id}/toggle-status");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true, 'data' => ['status' => Task::STATUS_IN_PROGRESS]]);
    }

    public function softDeleteAndRestore(\ApiTester $I)
    {
        $user = User::findOne(['email' => 'test@example.com']);
        $task = new Task([
            'title' => 'Task for delete/restore test',
            'due_date' => date('Y-m-d'),
            'assigned_to' => $user->id,
        ]);
        $task->save(false);

        // Soft delete
        $I->sendDELETE("/tasks/{$task->id}");
        $I->seeResponseCodeIs(204);

        $I->dontSeeRecord(Task::class, ['id' => $task->id]);

        // Restore
        $I->sendPATCH("/tasks/{$task->id}/restore");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);

        $restored = Task::findOne($task->id);
        $I->assertNotNull($restored);
    }

    public function accessDeniedForNonOwner(\ApiTester $I)
    {
        // Create a task owned by the main test user
        $owner = User::findOne(['email' => 'test@example.com']);
        $task = new Task([
            'title' => 'Owned task',
            'due_date' => date('Y-m-d'),
            'assigned_to' => $owner->id,
        ]);
        $task->save(false);

        // Create a different user (the intruder)
        $intruder = User::findOne(['email' => 'user@example.com']);
        if (!$intruder) {
            $intruder = new User([
                'name' => 'Normal User',
                'email' => 'user@example.com',
                'password_hash' => \Yii::$app->security->generatePasswordHash('userpass'),
                'role' => 'user',
                'auth_key' => \Yii::$app->security->generateRandomString(32),
            ]);
            $intruder->save(false);
        }

        // Generate JWT for the intruder
        $time = time();
        $key = Yii::$app->params['jwtSecret'];
        $payload = [
            'iss' => 'http://example.com',
            'aud' => 'http://example.org',
            'iat' => $time,
            'nbf' => $time,
            'exp' => $time + 3600,
            'uid' => $intruder->id,
            'role' => $intruder->role,
        ];
        $token = JWT::encode($payload, $key, 'HS256');

        $I->amBearerAuthenticated($token);

        $I->sendPUT("/tasks/{$task->id}", [
            'title' => 'Malicious Update Attempt',
            'version' => $task->version,
        ]);

        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['success' => false, 'data' => ['name' => 'Forbidden']]);
    }
}
