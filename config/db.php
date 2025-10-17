<?php

$config = [
    'class'    => 'yii\db\Connection',
    // copy these to db-local.php and fill in with your local settings
    'dsn'      => 'mysql:host=localhost;dbname=the_database_name',
    'username' => 'db_user', // placeholder
    'password' => 'db_password',
    // end of local settings

    'charset'  => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];

if (file_exists(__DIR__ . '/db-local.php')) {
    $config = \yii\helpers\ArrayHelper::merge(
        $config,
        require __DIR__ . '/db-local.php'
    );
}

return $config;
