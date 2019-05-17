<?php

// NOTE: Make sure this file is not accessible when deployed to production
if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('You are not allowed to access this file.');
}

// try some default UTF8 locales instead of "C" (else escapeshellarg() will remove characters)
setlocale(LC_CTYPE, "C.UTF-8") || setlocale(LC_CTYPE, "en_US.UTF-8");

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require('/usr/share/yii2/autoload.php');
require('/usr/share/yii2/yiisoft/yii2/Yii.php');

$config = require __DIR__ . '/../config/test.php';

require(__DIR__ . '/../functions.php');

(new yii\web\Application($config))->run();
