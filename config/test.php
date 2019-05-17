<?php
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/test_db.php';
$config = require(__DIR__ . '/web.php');

$config['id'] = 'basic-tests';
$config['components']['request']['cookieValidationKey'] = 'test';
$config['components']['request']['enableCsrfValidation'] = false;
// but if you absolutely need it set cookie domain to localhost
/*$config['components']['request']['csrfCookie'] = [
    'domain' => 'localhost',
];*/

/**
 * Application configuration shared by all test types
 */
return $config;