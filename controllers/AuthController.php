<?php

namespace app\controllers;

use app\filters\auth\HttpBearerAuth;
use Firebase\JWT\JWT;
use app\models\User;
use Yii;
use yii\rest\Controller;
use yii\web\UnauthorizedHttpException;

class AuthController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => [
                'login',
            ],
        ];

        return $behaviors;
    }

    /**
     * @return \yii\web\Response
     */
    public function actionLogin()
    {
        $request = Yii::$app->request;
        $email = $request->post('email');
        $password = $request->post('password');

        $user = User::findByEmail($email);

        if (!$user || !$user->validatePassword($password)) {
            throw new UnauthorizedHttpException('Invalid credentials.');
        }

        $time = time();
        $key = Yii::$app->params['jwtSecret'];

        $payload = [
            'iss' => 'http://example.com', // Issuer
            'aud' => 'http://example.org', // Audience
            'iat' => $time, // Issued at
            'nbf' => $time, // Not before
            'exp' => $time + 3600, // Expiration time
            'uid' => $user->id, // User ID
            'role' => $user->role, // User role
        ];
        $token = JWT::encode($payload, $key, 'HS256');

        return $this->asJson([
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
            'token' => $token,
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionData()
    {
        return $this->asJson([
            'success' => true,
        ]);
    }
}
