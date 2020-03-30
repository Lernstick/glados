<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'name' => 'GLaDOS',
    'version' => '1.0.7',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        [
            'class' => 'app\components\LanguageSelector',
            'supportedLanguages' => ['en', 'de'],
        ],
        [
            'class' => 'app\components\BootstrapSettings',
            'params' => $params,
        ],
        'app\components\BootstrapHistory',
    ],
    'timezone' => 'Europe/Zurich',
    'vendorPath' => '/usr/share/yii2',
    'modules' => [
        'dynagrid' => [
            'class' => '\kartik\dynagrid\Module',
        ],
        'gridview' => [
            'class' => '\kartik\grid\Module',
        ],
    ],
    'container' => [
        'definitions' => [
            'yii\widgets\LinkPager' => [
                'firstPageLabel' => '«',
                'lastPageLabel'  => '»',
                'nextPageLabel' => '›',
                'prevPageLabel' => '‹',
            ],
        ],
    ],
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
            'cache' => 'cache',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            #'showScriptName' => false,
            'rules' => [
                'activities' => 'activity/index',
                '<controller>s' => '<controller>/index',
                '<controller>/<id:\d+>' => '<controller>/view',
                '<controller>/<action:(update|delete|backup|restore|stop|kill)>/<id:\d+>' => '<controller>/<action>',
                'howto/img/<id>' => 'howto/img',
                'howto/<id>' => 'howto/view',
                'ticket/<action:(config|download|finish|notify|md5|status)>/<token:.*>' => 'ticket/<action>',
            ]
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
        'squashfs' => [
            'class' => 'app\components\Squashfs',
        ],
        'zip' => [
            'class' => 'app\components\Zip',
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
        'auth' => require(__DIR__ . '/auth.php'),
    ],
    'runtimePath' => '/var/lib/glados/runtime',
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    if($_SERVER['PATH_INFO'] != '/event/stream') {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['10.16.0.222', '192.168.0.115', '127.0.0.1']
        ];
    }

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['10.16.0.222', '192.168.0.115', '127.0.0.1']
    ];
}

return $config;
