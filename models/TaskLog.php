<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "task_log".
 *
 * @property int $id
 * @property int $task_id
 * @property int|null $user_id
 * @property string $operation
 * @property string $changes
 * @property int $created_at
 *
 * @property Task $task
 * @property User $user
 */
class TaskLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'default', 'value' => null],
            [['task_id', 'operation', 'changes', 'created_at'], 'required'],
            [['task_id', 'user_id', 'created_at'], 'integer'],
            [['changes'], 'safe'],
            [['operation'], 'string', 'max' => 32],
            [['task_id'], 'exist', 'skipOnError' => true, 'targetClass' => Task::class, 'targetAttribute' => ['task_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task ID',
            'user_id' => 'User ID',
            'operation' => 'Operation',
            'changes' => 'Changes',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Task]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

}
