<?php

namespace app\controllers;

use Yii;
use yii\rest\ActiveController;
use app\models\Tag;
use yii\filters\auth\HttpBearerAuth;
use app\components\JwtHttpBearerAuth;

class TagController extends ActiveController
{
    public $modelClass = 'app\models\Tag';

    public function behaviors()
    {
        $b = parent::behaviors();
        $b['authenticator'] = [
            'class' => JwtHttpBearerAuth::class,
            'except' => [], // require auth for tags
        ];
        return $b;
    }
}
