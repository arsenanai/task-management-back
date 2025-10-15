<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password_hash
 * @property string $role
 * @property string $auth_key
 * @property int $created_at
 * @property int $updated_at
 *
 * @property TaskLog[] $taskLogs
 * @property Task[] $tasks
 */
class User extends ActiveRecord implements IdentityInterface
{
    public $id;
    public $username;
    public $password;
    public $authKey;
    public $accessToken;

    // Enum values for role
    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';

    public static function tableName() { return 'user'; }

    // Validation rules
    public function rules()
    {
        return [
            [['name', 'email', 'password_hash', 'auth_key' /*, 'created_at', 'updated_at'*/], 'required'],
            [['role'], 'default', 'value' => 'user'],
            ['role', 'in', 'range' => array_keys(self::optsRole())],
            [['created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 128],
            [['email', 'password_hash'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['email'], 'unique'],
            [['email'], 'email'],
        ];
    }

    // Finders
    public static function findIdentity($id){ return static::findOne($id); }

    // token handling delegated to JwtHttpBearerAuth
    public static function findIdentityByAccessToken($token, $type = null){ return null; }

    public static function findByUsername($username)
    {
        $username = trim($username);
        if (empty($username)) {
            return null;
        }
        return static::findOne('username', $username);
    }

    // Getters
    public function getId() { return $this->id; }

    public function getAuthKey() { return $this->authKey; }

    public function getTaskLogs()
    {
        return $this->hasMany(TaskLog::class, ['user_id' => 'id']);
    }

    public function getTasks()
    {
        return $this->hasMany(Task::class, ['assigned_to' => 'id']);
    }

    // Validators
    public function validateAuthKey($authKey) { return $this->authKey === $authKey; }

    public function validatePassword($password) { return $this->password === $password; }

    // Setters
    public function setRoleToAdmin() { $this->role = self::ROLE_ADMIN; }

    public function setRoleToUser() { $this->role = self::ROLE_USER; }

    // Helpers
    public static function optsRole()
    {
        return [
            self::ROLE_ADMIN => 'admin',
            self::ROLE_USER => 'user',
        ];
    }

    public function displayRole()
    {
        return self::optsRole()[$this->role];
    }

    public function isRoleAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isRoleUser()
    {
        return $this->role === self::ROLE_USER;
    }

    public function behaviors() { return [TimestampBehavior::class]; }
}
