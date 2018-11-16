<?php

Yii::setAlias('@tests', dirname(__DIR__) . '/tests');

$params = require(__DIR__ . '/params.php');
$db = require(__DIR__ . '/db.php');

require(__DIR__ . '/../functions.php');

return [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'gii'],
    'timezone' => 'Europe/Zurich',
    'vendorPath' => '/usr/share/yii2',
    'controllerNamespace' => 'app\commands',
    'modules' => [
        'gii' => 'yii\gii\Module',
    ],
    'components' => [
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'formatter' => [
            'class' => 'app\components\customFormatter',
            'defaultTimeZone' => 'Europe/Zurich',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => '@runtime/logs/profile.log',                    
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'scriptUrl' => $params['scriptUrl'],
            'rules' => [
                'activities' => 'activity/index',
                '<controller>s' => '<controller>/index',
                '<controller>/<id:\d+>' => '<controller>/view',
                '<controller>/<action:(update|delete|backup|restore|stop|kill)>/<id:\d+>' => '<controller>/<action>',
                'howto/img/<id>' => 'howto/img',
                'howto/<id>' => 'howto/view',
                'ticket/<action:(config|download|finish|notify|md5)>/<token:.*>' => 'ticket/<action>',
            ]
        ],
        'db' => $db,
    ],
    'params' => $params,
    'runtimePath' => '/var/lib/glados/runtime',
];
