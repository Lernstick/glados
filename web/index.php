<?php

// try some default UTF8 locales instead of "C" (else escapeshellarg() will remove characters)
setlocale(LC_CTYPE, "C.UTF-8") || setlocale(LC_CTYPE, "en_US.UTF-8");

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require('/usr/share/yii2/autoload.php');
require('/usr/share/yii2/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');

require(__DIR__ . '/../functions.php');

(new yii\web\Application($config))->run();
