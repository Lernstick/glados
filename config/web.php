<?php

$params = require(__DIR__ . '/params.php');
$defaultParams = require(__DIR__ . '/defaultParams.php');

$config = [
    'id' => 'basic',
    'name' => 'GLaDOS',
    'version' => '1.0.11',
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
            'defaultParams' => $defaultParams,
        ],
        'app\components\BootstrapHistory',
    ],
    'timezone' => 'Europe/Zurich',
    'vendorPath' => '/usr/share/yii2',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
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
            'app\widgets\CustomPager' => [
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
                'screencapture/<ticket_id:\d+>/<file:[\w\.]+>' => 'screencapture/view',
                '<controller>/<id:\d+>' => '<controller>/view',
                '<controller>/<action:(update|delete|backup|restore|stop|kill)>/<id:\d+>' => '<controller>/<action>',
                'howto/img/<id>' => 'howto/img',
                'howto/<id>' => 'howto/view',
                'ticket/<action:(config|download|finish|notify|md5|status|live)>/<token:.*>' => 'ticket/<action>',
                'backup/<action:file>/<ticket_id:\d+>' => 'backup/<action>',
                'event/<action:(agent)>/<token:.*>' => 'event/<action>',
                'monitor' => 'monitor/view',
                'result/<action:view>/<token:.*>' => 'result/<action>',
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
                    'maskVars' => [
                        '_POST.LoginForm.password',
                        '_POST.AuthTestForm.password',
                        '_POST.AuthActiveDirectory.query_password',
                        '_POST.AuthOpenLdap.query_password',
                        '_POST.AuthGenericLdap.query_password'
                    ],
                ],
            ],
        ],
        'mutex' => [
            'class' => 'yii\mutex\FileMutex',
        ],
        'db' => require(__DIR__ . '/db.php'),
        'auth' => require(__DIR__ . '/auth.php'),
    ],
    'runtimePath' => '/var/lib/glados/runtime',
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    if(array_key_exists('PATH_INFO', $_SERVER) && $_SERVER['PATH_INFO'] != '/event/stream') {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['10.16.0.222', '192.168.0.248', '127.0.0.1']
        ];
    }

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'allowedIPs' => ['10.16.0.222', '192.168.0.248', '127.0.0.1']
    ];
}

return $config;
