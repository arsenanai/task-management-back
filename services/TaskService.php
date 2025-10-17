<?php

namespace app\services;

use app\models\Task;
use Yii;
use yii\base\Component;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnprocessableEntityHttpException;

class TaskService extends Component
{
    /**
     * @param array $params
     * @param bool $isCursor
     * @return ActiveDataProvider
     */
    public function search(array $params, bool $isCursor = false): ActiveDataProvider
    {
        $query = Task::find()->with(['tags', 'assignedUser']);

        // Filtering logic
        if (!empty($params['status'])) {
            $query->andWhere(['task.status' => $params['status']]);
        }
        if (!empty($params['priority'])) {
            $query->andWhere(['task.priority' => $params['priority']]);
        }
        if (!empty($params['assigned_to'])) {
            $query->andWhere(['assigned_to' => $params['assigned_to']]);
        }
        if (!empty($params['keyword'])) {
            $query->andWhere(['or',
                ['like', 'title', $params['keyword']],
                ['like', 'description', $params['keyword']],
            ]);
        }
        if (!empty($params['due_from'])) {
            $query->andWhere(['>=', 'task.due_date', $params['due_from']]);
        }
        if (!empty($params['due_to'])) {
            $query->andWhere(['<=', 'task.due_date', $params['due_to']]);
        }
        if (!empty($params['tags'])) {
            $tagIds = explode(',', $params['tags']);
            $query->innerJoinWith('tags', false)
                ->andWhere(['tag.id' => $tagIds])
                ->groupBy('task.id')
                ->having(['COUNT(DISTINCT tag.id)' => count($tagIds)]);
        }

        $sortConfig = [
            'defaultOrder' => ['created_at' => SORT_DESC],
            'attributes' => [
                'created_at',
                'due_date',
                'priority',
                'title',
                'status'
            ],
        ];
        $sortConfig['params'] = $params;

        return new ActiveDataProvider([
            'query' => $query,
            'sort' => $sortConfig,
            'pagination' => $isCursor ? false : [],
        ]);
    }

    /**
     * @param int $id
     * @return Task
     * @throws NotFoundHttpException
     */
    public function getTaskById(int $id): Task
    {
        $task = Task::find()
            ->with(['tags', 'assignedUser'])
            ->where(['id' => $id])
            ->one();

        if (!$task) {
            throw new NotFoundHttpException('task.not_found');
        }

        $this->checkAccess('view', $task);

        return $task;
    }

    /**
     * @param array $data
     * @return Task
     * @throws UnprocessableEntityHttpException
     */
    public function createTask(array $data): Task
    {
        $this->checkAccess('create');

        $task = new Task();
        $task->load($data, '');

        if (!empty($data['tag_ids'])) {
            $task->metadata = array_merge((array) $task->metadata, ['tag_ids' => $data['tag_ids']]);
        }

        if ($task->save()) {
            Yii::$app->response->statusCode = 201; // Created
            return $task;
        }

        throw new UnprocessableEntityHttpException(json_encode($task->errors));
    }

    /**
     * @param int $id
     * @param array $data
     * @return Task
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     * @throws UnprocessableEntityHttpException
     */
    public function updateTask(int $id, array $data): Task
    {
        // Fetch the task directly to ensure we control the authorization flow
        // and don't rely on another method's implementation details.
        $task = Task::findOne($id);
        if (!$task) {
            throw new NotFoundHttpException('task.not_found');
        }
        $this->checkAccess('update', $task);

        if (!isset($data['version'])) {
            throw new ConflictHttpException('task.version_required');
        }

        $task->load($data, '');

        if (!empty($data['tag_ids'])) {
            $task->metadata = array_merge((array) $task->metadata, ['tag_ids' => $data['tag_ids']]);
        }

        try {
            if ($task->save()) {
                return $task;
            }
        } catch (StaleObjectException $e) {
            throw new ConflictHttpException('task.stale_object');
        }

        throw new UnprocessableEntityHttpException(json_encode($task->errors));
    }

    /**
     * @param int $id
     * @return bool
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function deleteTask(int $id): bool
    {
        $task = Task::findOne($id);
        $this->checkAccess('delete', $task);

        try {
            // For soft delete, delete() returns false because the event is marked as invalid.
            if ($task->delete() === false) {
                return true; // Soft delete was successful
            }
        } catch (StaleObjectException $e) {
            throw new ConflictHttpException('task.stale_object');
        }

        // This part should ideally not be reached with soft delete behavior.
        // If it returns true, it means a hard delete happened.
        // If it returns something else, it's an unexpected failure.
        throw new ServerErrorHttpException('task.delete_failed');
    }

    public function restoreTask(int $id): Task
    {
        $task = Task::find()->where(['id' => $id])->withDeleted()->one();
        if (!$task) {
            throw new NotFoundHttpException('task.not_found');
        }

        $this->checkAccess('restore', $task);

        if ($task->restore()) {
            return $task;
        }

        throw new ServerErrorHttpException('task.restore_failed');
    }

    public function toggleTaskStatus(int $id): Task
    {
        $task = $this->getTaskById($id);
        $this->checkAccess('toggle-status', $task);

        if ($task->status === Task::STATUS_COMPLETED) {
            throw new BadRequestHttpException('task.completed_immutable');
        }

        $cycle = [
            Task::STATUS_PENDING => Task::STATUS_IN_PROGRESS,
            Task::STATUS_IN_PROGRESS => Task::STATUS_COMPLETED,
        ];

        $task->status = $cycle[$task->status] ?? Task::STATUS_PENDING;

        try {
            if ($task->save()) {
                return $task;
            }
        } catch (StaleObjectException $e) {
            throw new ConflictHttpException('task.stale_object');
        }

        throw new ServerErrorHttpException('task.toggle_status.failed');
    }

    private function checkAccess(string $action, ?Task $model = null): void
    {
        $user = Yii::$app->user->identity;

        if ($user->role === 'admin') {
            return;
        }

        // Allow 'create' for any authenticated user. For all other actions, the user must be the one the task is assigned to.
        if ($action === 'create' || ($model && $model->assigned_to == $user->id)) {
            return;
        }

        throw new \yii\web\ForbiddenHttpException('task.access_denied');
    }
}
