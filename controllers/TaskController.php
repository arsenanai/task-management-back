<?php

namespace app\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\web\NotFoundHttpException;
use yii\web\ConflictHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use yii\filters\VerbFilter;
use app\models\Task;
use app\filters\auth\HttpBearerAuth;
use app\services\TaskService;
use yii\db\StaleObjectException;

class TaskController extends ActiveController
{
    public $modelClass = 'app\models\Task';

    private TaskService $taskService;

    public function __construct($id, $module, TaskService $taskService, $config = [])
    {
        $this->taskService = $taskService;
        parent::__construct($id, $module, $config);
    }
    public function behaviors()
    {
        $b = parent::behaviors();

        // JWT Auth
        $b['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        // HTTP verbs
        $b['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index'  => ['GET', 'HEAD'],
                'view'   => ['GET', 'HEAD'],
                'create' => ['POST'],
                'toggle-status' => ['PATCH'],
                'restore' => ['PATCH'],
                'delete' => ['DELETE'],
                'update' => ['PUT'],
            ],
        ];

        return $b;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    /**
     * GET /tasks
     */
    public function actionIndex()
    {
        $params = Yii::$app->request->queryParams;
        return $this->taskService->search($params, isset($params['cursor']));
    }

    /**
     * GET /tasks/{id}
     */
    public function actionView($id)
    {
        return $this->taskService->getTaskById($id);
    }

    /**
     * POST /tasks
     */
    public function actionCreate()
    {
        $data = Yii::$app->request->post();
        return $this->taskService->createTask($data);
    }

    /**
     * PUT /tasks/{id}
     * Requires `version` in payload for optimistic lock validation
     */
    public function actionUpdate($id)
    {
        $data = Yii::$app->request->getBodyParams();
        return $this->taskService->updateTask($id, $data);
    }

    /**
     * DELETE /tasks/{id}
     * Soft delete
     */
    public function actionDelete($id)
    {
        if ($this->taskService->deleteTask($id)) {
            Yii::$app->response->statusCode = 204; // No Content
            return;
        }
    }

    /**
     * PATCH /tasks/{id}/restore
     */
    public function actionRestore($id)
    {
        return $this->taskService->restoreTask($id);
    }

    /**
     * PATCH /tasks/{id}/toggle-status
     */
    public function actionToggleStatus($id)
    {
        return $this->taskService->toggleTaskStatus($id);
    }

    /**
     * Finds the Task model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
