<?php

use app\models\Task;
use yii\db\StaleObjectException;

class TaskTest extends \Codeception\Test\Unit
{
    protected $tester;
    private $task;

    public function _before()
    {
        $db = \Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
            $db->createCommand()->truncateTable('task_tag')->execute();
            $db->createCommand()->truncateTable('task_log')->execute();
            $db->createCommand()->truncateTable('task')->execute();
            $db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->task = new Task([
            'title'    => 'Test Task',
            'due_date' => date('Y-m-d', strtotime('+1 day')),
            'status'   => Task::STATUS_PENDING,
            'version'  => 0,
        ]);
        $this->task->save(false);
        $this->task->refresh();
    }

    public function _after()
    {
        // Use static deleteAll to avoid StaleObjectException if version changed
        if ($this->task && $this->task->id) {
            try {
                Task::deleteAll(['id' => $this->task->id]);
            } catch (\Throwable $e) {
                // Ignore cleanup exceptions
            }
        }
    }

    public function testTitleValidation()
    {
        $t = new Task();
        $t->title = 'abc';
        $this->assertFalse($t->validate(['title']));
    }

    public function testDueDateCannotBePastForPending()
    {
        $t = new Task([
            'title' => 'Valid title',
            'due_date' => date('Y-m-d', strtotime('-1 day')),
            'status' => Task::STATUS_PENDING,
        ]);
        $this->assertFalse($t->validate(['due_date']));
    }

    public function testInitialSaveAndVersionStartsAtZero()
    {
        $task = Task::findOne($this->task->id);
        $this->assertEquals(0, $task->version, 'Version should start at 0');
    }

    public function testVersionIncrementsOnSave()
    {
        $task = Task::findOne($this->task->id);
        $task->status = Task::STATUS_IN_PROGRESS;
        $task->save(false);

        $updated = Task::findOne($this->task->id);
        $this->assertEquals(1, $updated->version, 'Version should increment after update');
    }

    public function testConcurrentUpdateThrowsStaleObjectException()
    {
        $t1 = Task::findOne($this->task->id);
        $t2 = Task::findOne($this->task->id);

        $t1->status = Task::STATUS_IN_PROGRESS;
        $t1->save(false); // version increments to 1

        $t2->status = Task::STATUS_COMPLETED; // stale version = 0

        $this->expectException(StaleObjectException::class);
        $t2->save(false);
    }

    public function testConcurrentDeleteThrowsStaleObjectException()
    {
        $t1 = Task::findOne($this->task->id);
        $t2 = Task::findOne($this->task->id);

        $t1->title = 'Changed title';
        $t1->save(false); // version -> 1

        $this->expectException(StaleObjectException::class);
        // SoftDeleteBehavior's beforeDelete event will throw the exception
        // because it tries to update a stale record.
        $t2->delete();
    }

    public function testManualVersionResetCausesFailure()
    {
        $task = Task::findOne($this->task->id);
        $task->status = Task::STATUS_IN_PROGRESS;
        $task->save(false);

        // Simulate bad manual version manipulation
        $task->version = 0;

        $this->expectException(StaleObjectException::class);
        $task->save(false);
    }

    public function testMultipleSequentialUpdatesWorkFine()
    {
        $task = Task::findOne($this->task->id);

        $task->status = Task::STATUS_IN_PROGRESS;
        $task->save(false);
        $this->assertEquals(1, $task->version);

        $task->status = Task::STATUS_COMPLETED;
        $task->save(false);
        $this->assertEquals(2, $task->version);
    }

    public function testDeleteWithCorrectVersionSucceeds()
    {
        $task = Task::findOne($this->task->id);
        $task->delete();

        $this->assertNull(Task::findOne($this->task->id));
    }
}
