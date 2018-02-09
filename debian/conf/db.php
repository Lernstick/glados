<?php

/**
 * Read configuration from dbconfig-common
 * You can regenerate it using: dpkg-reconfigure -plow glados
 */
if (is_readable('/etc/glados/config-db.php')) {
    require('/etc/glados/config-db.php');
}

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=' . ( empty($dbserver) ? 'localhost' : $dbserver ) . ';dbname=' . $dbname,
    'username' => $dbuser,
    'password' => $dbpass,
    'charset' => 'utf8',
];
