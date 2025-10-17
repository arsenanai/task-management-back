<?php

namespace app\filters\auth;

use Yii;
use yii\filters\auth\AuthMethod;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use yii\web\UnauthorizedHttpException;

class HttpBearerAuth extends AuthMethod
{
    public $realm = 'api';

    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');
        if ($authHeader !== null && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];
            try {
                $decoded = JWT::decode($token, new Key(Yii::$app->params['jwtSecret'], 'HS256'));
                $identity = $user->loginByAccessToken($decoded, get_class($this));
                if ($identity === null) {
                    $this->handleFailure($response);
                }
                return $identity;
            } catch (\Exception $e) {
                // Log the exception if needed
                Yii::error('JWT validation failed: ' . $e->getMessage(), __METHOD__);
                $this->handleFailure($response);
            }
        }

        return null;
    }

    public function challenge($response)
    {
        $response->getHeaders()->set('WWW-Authenticate', "Bearer realm=\"{$this->realm}\"");
    }
}
