<?php

namespace app\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use app\models\query\SoftDeleteQuery;

class SoftDeleteBehavior extends Behavior
{
    /**
     * @var string The attribute to store the soft-delete timestamp
     */
    public $softDeleteAttribute = 'deleted_at';

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'softDelete',
        ];
    }

    /**
     * @return int
     */
    public function softDelete($event): void
    {
        // Manually update the record to set the deleted_at timestamp
        $this->owner->{$this->softDeleteAttribute} = time();
        $this->owner->save(false, [$this->softDeleteAttribute, 'version', 'updated_at']);

        // Stop the event to prevent physical deletion
        $event->isValid = false;
    }

    public function restore()
    {
        $this->owner->{$this->softDeleteAttribute} = null;
        return $this->owner->save(false, [$this->softDeleteAttribute]);
    }
}
