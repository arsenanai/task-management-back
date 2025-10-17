<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\behaviors\SoftDeleteBehavior;
use app\behaviors\AuditLogBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

/**
 * This is the model class for table "task".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property string|null $due_date
 * @property int|null $assigned_to
 * @property string|null $metadata
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $deleted_at
 * @property int $version
 *
 * @property User $assignedTo
 * @property Tag[] $tags
 * @property TaskLog[] $taskLogs
 * @property TaskTag[] $taskTags
 */
class Task extends ActiveRecord
{
    /**
     * Constants for status and priority
     */
    public const STATUS_PENDING = 1;
    public const STATUS_IN_PROGRESS = 2;
    public const STATUS_COMPLETED = 3;
    public const PRIORITY_LOW = 1;
    public const PRIORITY_MEDIUM = 2;
    public const PRIORITY_HIGH = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Defaults
            [['description', 'due_date', 'assigned_to', 'metadata', 'deleted_at'], 'default', 'value' => null],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['priority'], 'default', 'value' => self::PRIORITY_MEDIUM],
            [['version'], 'default', 'value' => 0],

            // Required
            [['title'], 'required'],

            // Types
            [['title'], 'string', 'min' => 5, 'max' => 255],
            [['description'], 'string'],
            [['assigned_to', 'created_at', 'updated_at', 'deleted_at', 'version', 'status', 'priority'], 'integer'],
            [['metadata'], 'safe'],
            [['due_date'], 'date', 'format' => 'php:Y-m-d'],

            // Validation Rules
            ['due_date', 'validateDueDate'], // Custom due date validator
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            ['priority', 'in', 'range' => array_keys(self::optsPriority())],
            [['assigned_to'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['assigned_to' => 'id']],
            ['status', 'validateCompletedTask'], // Custom validator for immutability
        ];
    }

    public function validateDueDate($attribute)
    {
        if (!$this->hasErrors() && $this->$attribute) {
            $due = strtotime($this->$attribute . ' 00:00:00');
            if (in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS], true) && $due < strtotime('today')) {
                $this->addError($attribute, 'Due date cannot be in the past for pending or in_progress tasks.');
            }
        }
    }

    /**
     * Validator to make completed tasks immutable.
     * @param $attribute
     */
    public function validateCompletedTask($attribute)
    {
        if (!$this->isNewRecord && $this->isAttributeChanged($attribute) && $this->getOldAttribute($attribute) == self::STATUS_COMPLETED) {
            // This task was already completed, it cannot be changed.
            $this->addError($attribute, 'A completed task cannot be modified.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'description' => 'Description',
            'status' => 'Status',
            'priority' => 'Priority',
            'due_date' => 'Due Date',
            'assigned_to' => 'Assigned To',
            'metadata' => 'Metadata',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
            'version' => 'Version',
        ];
    }

    public function behaviors()
    {
        return [
             'timestamp' => [
                 'class' => TimestampBehavior::class,
                 'createdAtAttribute' => 'created_at',
                 'updatedAtAttribute' => 'updated_at',
                 'value' => new Expression('UNIX_TIMESTAMP()'),
             ],
             'softDelete' => [
                 'class' => SoftDeleteBehavior::class,
                 'softDeleteAttribute' => 'deleted_at',
             ],
             'audit' => [
                 'class' => AuditLogBehavior::class,
                 // 'ignoredAttributes' => ['updated_at'], // Example: prevent audit logs on timestamp changes
             ],
         ];
    }

    /**
     * Gets query for [[AssignedTo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssignedUser()
    {
        return $this->hasOne(User::class, ['id' => 'assigned_to']);
    }

    /**
     * Gets query for [[Tags]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])->viaTable('task_tag', ['task_id' => 'id']);
    }

    /**
     * Gets query for [[TaskLogs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskLogs()
    {
        return $this->hasMany(TaskLog::class, ['task_id' => 'id']);
    }

    /**
     * Gets query for [[TaskTags]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskTags()
    {
        return $this->hasMany(TaskTag::class, ['task_id' => 'id']);
    }


    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    /**
     * column priority ENUM value labels
     * @return string[]
     */
    public static function optsPriority()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
        ];
    }

    /**
     * @return string
     */
    public function displayStatus()
    {
        return self::optsStatus()[$this->status];
    }

    /**
     * @return bool
     */
    public function isStatusPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function setStatusToPending()
    {
        $this->status = self::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isStatusInprogress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function setStatusToInprogress()
    {
        $this->status = self::STATUS_IN_PROGRESS;
    }

    /**
     * @return bool
     */
    public function isStatusCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function setStatusToCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
    }

    /**
     * @return string
     */
    public function displayPriority()
    {
        return self::optsPriority()[$this->priority];
    }

    /**
     * @return bool
     */
    public function isPriorityLow()
    {
        return $this->priority === self::PRIORITY_LOW;
    }

    public function setPriorityToLow()
    {
        $this->priority = self::PRIORITY_LOW;
    }

    /**
     * @return bool
     */
    public function isPriorityMedium()
    {
        return $this->priority === self::PRIORITY_MEDIUM;
    }

    public function setPriorityToMedium()
    {
        $this->priority = self::PRIORITY_MEDIUM;
    }

    /**
     * @return bool
     */
    public function isPriorityHigh()
    {
        return $this->priority === self::PRIORITY_HIGH;
    }

    public function setPriorityToHigh()
    {
        $this->priority = self::PRIORITY_HIGH;
    }

    public function optimisticLock()
    {
        return 'version';
    }

    public static function find()
    {
        return new \app\models\query\SoftDeleteQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['tags'] = function () { return $this->tags; };
        $fields['assigned_user'] = function () { return $this->assignedUser; };
        return $fields;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        // handle tag assignment if metadata contains 'tag_ids'
        if (isset($this->metadata['tag_ids']) && is_array($this->metadata['tag_ids'])) {
            $this->linkTags($this->metadata['tag_ids']);
        }
    }

    public function linkTags(array $tagIds)
    {
        $existing = ArrayHelper::getColumn($this->tags, 'id');
        $toAdd = array_diff($tagIds, $existing);
        $toRemove = array_diff($existing, $tagIds);
        foreach ($toAdd as $tid) {
            if (Tag::findOne($tid)) {
                $this->link('tags', Tag::findOne($tid));
            }
        }
        foreach ($toRemove as $tid) {
            $this->unlink('tags', Tag::findOne($tid), true);
        }
    }

}
