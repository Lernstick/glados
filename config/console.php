<?php

Yii::setAlias('@tests', dirname(__DIR__) . '/tests');

$params = require(__DIR__ . '/params.php');
$defaultParams = require(__DIR__ . '/defaultParams.php');
$db = require(__DIR__ . '/db.php');

require(__DIR__ . '/../functions.php');

return [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        'gii',
        [
            'class' => 'app\components\BootstrapSettings',
            'params' => $params,
            'defaultParams' => $defaultParams,
        ],
        'app\components\BootstrapElasticsearch',
    ],
    'language' => 'en',
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
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en',
                ],
            ],
        ],
        'file' => [
            'class' => 'app\components\File',
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
        'db' => $db,
        'mutex' => [
            'class' => 'yii\mutex\MysqlMutex',
        ],
    ],
    'params' => $params,
    'runtimePath' => '/var/lib/glados/runtime',
];
