<?php

$params = [
    'adminEmail'  => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName'  => 'Example.com mailer',
];

if (file_exists(__DIR__ . '/params-local.php')) {
    $params = \yii\helpers\ArrayHelper::merge(
        $params,
        require __DIR__ . '/params-local.php'
    );
}

return $params;
