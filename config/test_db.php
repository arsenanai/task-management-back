<?php

$config = [
    'class'    => 'yii\db\Connection',

    // copy these to test_db-local.php and fill in with your local settings
    'dsn'      => 'mysql:host=localhost;dbname=task_management_test',
    'username' => 'db_user', // placeholder
    'password' => '',
    // end of local settings

    'charset'  => 'utf8',
];

if (file_exists(__DIR__ . '/test_db-local.php')) {
    $config = \yii\helpers\ArrayHelper::merge(
        $config,
        require __DIR__ . '/test_db-local.php'
    );
}

return $config;
