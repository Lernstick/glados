<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'timezone' => 'Europe/Zurich',
    'vendorPath' => '/usr/share/yii2',
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '76931daaa7c7f134531e7938b3a9052c',
        ],
        'response' => [
            'class' =>  'app\components\customResponse',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'assetManager' => [
            'linkAssets' => true,
            'appendTimestamp' => true,
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            #'showScriptName' => false,
            'rules' => [
                'activities' => 'activity/index',
                '<controller>s' => '<controller>/index',
                '<controller>/<id:\d+>' => '<controller>/view',
                '<controller>/<action:(update|delete|backup|restore|stop|kill)>/<id:\d+>' => '<controller>/<action>',
                'howto/<id>' => 'howto/view',
                'ticket/<action:(config|download|finish|notify|md5)>/<token:\w+\d+>' => 'ticket/<action>',
            ]
        ],
        'file' => [
            'class' => 'app\components\File',
        ],
        'squashfs' => [
            'class' => 'app\components\Squashfs',
        ],
        'formatter' => [
            'class' => 'app\components\customFormatter',
            'defaultTimeZone' => 'Europe/Zurich',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['10.16.0.222', '192.168.0.115', '127.0.0.1']
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['10.16.0.222', '192.168.0.115', '127.0.0.1']
    ];
}

return $config;
