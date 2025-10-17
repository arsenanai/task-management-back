<?php

namespace tests\api;

use app\models\User;

class AuthCest
{
    public function _before(\ApiTester $I)
    {
        // This user is created before each test in this file and cleaned up afterwards.
        $user = User::findOne(['email' => 'test@example.com']);
        if (!$user) {
            $I->haveRecord(User::class, [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password_hash' => \Yii::$app->security->generatePasswordHash('password'),
                'auth_key' => \Yii::$app->security->generateRandomString(),
                'role' => 'user',
            ]);
        }
    }

    public function testLoginWithValidCredentials(\ApiTester $I)
    {
        $I->sendPost('/auth/login', ['email' => 'test@example.com', 'password' => 'password']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeResponseMatchesJsonType(['data' => ['token' => 'string']]);
    }

    public function testLoginWithInvalidCredentials(\ApiTester $I)
    {
        $I->sendPost('/auth/login', ['email' => 'test@example.com', 'password' => 'wrongpassword']);
        $I->seeResponseCodeIs(401); // Unauthorized
        $I->seeResponseContainsJson(['success' => false, 'data' => ['name' => 'Unauthorized']]);
    }
}
