<?php

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'dev');

require('/usr/share/yii2/autoload.php');
require('/usr/share/yii2/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');

require(__DIR__ . '/../functions.php');

(new yii\web\Application($config))->run();
