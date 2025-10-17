<?php

namespace app\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use app\models\TaskLog;
use Yii;

class AuditLogBehavior extends Behavior
{
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'logCreate',
            ActiveRecord::EVENT_AFTER_UPDATE => 'logUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'logDelete',
        ];
    }

    protected function log($operation, $changes = [])
    {
        if (YII_ENV_TEST) {
            return;
        }
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $log = new TaskLog();
        $log->task_id = $owner->id;
        $log->user_id = Yii::$app->user->id ?? null;
        $log->operation = $operation;
        $log->changes = json_encode($changes);
        $log->created_at = time();
        $log->save(false);
    }

    public function logCreate($event)
    {
        $this->log('create', $this->owner->attributes);
    }
    public function logUpdate($event)
    {
        $changes = $this->owner->getDirtyAttributes();
        $this->log('update', $changes);
    }
    public function logDelete($event)
    {
        $this->log('delete', $this->owner->attributes);
    }
}
