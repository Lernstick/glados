<?php
/**
 * Application requirement checker script.
 *
 * In order to run this script use the following console command:
 * php requirements.php
 *
 * In order to run this script from the web, you should copy it to the web root.
 * If you are using Linux you can create a hard link instead, using the following command:
 * ln requirements.php ../requirements.php
 */

// you may need to adjust this path to the correct Yii framework path
$frameworkPath = '/usr/share/yii2/yiisoft/yii2';

if (!is_dir($frameworkPath)) {
    echo '<h1>Error</h1>';
    echo '<p><strong>The path to yii framework seems to be incorrect.</strong></p>';
    echo '<p>You need to install Yii framework via composer or adjust the framework path in file <abbr title="' . __FILE__ . '">' . basename(__FILE__) . '</abbr>.</p>';
    echo '<p>Please refer to the <abbr title="' . dirname(__FILE__) . '/README.md">README</abbr> on how to install Yii.</p>';
}

require_once($frameworkPath . '/requirements/YiiRequirementChecker.php');
$requirementsChecker = new YiiRequirementChecker();

$gdMemo = $imagickMemo = 'Either GD PHP extension with FreeType support or ImageMagick PHP extension with PNG support is required for image CAPTCHA.';
$gdOK = $imagickOK = false;

if (extension_loaded('imagick')) {
    $imagick = new Imagick();
    $imagickFormats = $imagick->queryFormats('PNG');
    if (in_array('PNG', $imagickFormats)) {
        $imagickOK = true;
    } else {
        $imagickMemo = 'Imagick extension should be installed with PNG support in order to be used for image CAPTCHA.';
    }
}

if (extension_loaded('gd')) {
    $gdInfo = gd_info();
    if (!empty($gdInfo['FreeType Support'])) {
        $gdOK = true;
    } else {
        $gdMemo = 'GD extension should be installed with FreeType support in order to be used for image CAPTCHA.';
    }
}

/**
 * Adjust requirements according to your application specifics.
 */
$requirements = array(
    // Database :
    array(
        'name' => 'PDO extension',
        'mandatory' => true,
        'condition' => extension_loaded('pdo'),
        'by' => 'All DB-related classes',
    ),
    /*array(
        'name' => 'PDO SQLite extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_sqlite'),
        'by' => 'All DB-related classes',
        'memo' => 'Required for SQLite database.',
    ),*/
    array(
        'name' => 'PDO MySQL extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_mysql'),
        'by' => 'All DB-related classes',
        'memo' => 'Required for MySQL database.',
    ),
    /*array(
        'name' => 'PDO PostgreSQL extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_pgsql'),
        'by' => 'All DB-related classes',
        'memo' => 'Required for PostgreSQL database.',
    ),*/
    // Cache :
    array(
        'name' => 'Memcache extension',
        'mandatory' => false,
        'condition' => extension_loaded('memcache') || extension_loaded('memcached'),
        'by' => '<a href="http://www.yiiframework.com/doc-2.0/yii-caching-memcache.html">MemCache</a>',
        'memo' => extension_loaded('memcached') ? 'To use memcached set <a href="http://www.yiiframework.com/doc-2.0/yii-caching-memcache.html#$useMemcached-detail">MemCache::useMemcached</a> to <code>true</code>.' : ''
    ),
    array(
        'name' => 'APC extension',
        'mandatory' => false,
        'condition' => extension_loaded('apc'),
        'by' => '<a href="http://www.yiiframework.com/doc-2.0/yii-caching-apccache.html">ApcCache</a>',
    ),
    // CAPTCHA:
    array(
        'name' => 'GD PHP extension with FreeType support',
        'mandatory' => false,
        'condition' => $gdOK,
        'by' => '<a href="http://www.yiiframework.com/doc-2.0/yii-captcha-captcha.html">Captcha</a>',
        'memo' => $gdMemo,
    ),
    array(
        'name' => 'ImageMagick PHP extension with PNG support',
        'mandatory' => false,
        'condition' => $imagickOK,
        'by' => '<a href="http://www.yiiframework.com/doc-2.0/yii-captcha-captcha.html">Captcha</a>',
        'memo' => $imagickMemo,
    ),
    // PHP ini :
    'phpExposePhp' => array(
        'name' => 'Expose PHP',
        'mandatory' => false,
        'condition' => $requirementsChecker->checkPhpIniOff("expose_php"),
        'by' => 'Security reasons',
        'memo' => '<code>expose_php</code> should be disabled at <code>php.ini</code>',
    ),
    'phpAllowUrlInclude' => array(
        'name' => 'PHP allow url include',
        'mandatory' => false,
        'condition' => $requirementsChecker->checkPhpIniOff("allow_url_include"),
        'by' => 'Security reasons',
        'memo' => '<code>allow_url_include</code> should be disabled at <code>php.ini</code>',
    ),
    'phpSmtp' => array(
        'name' => 'PHP mail SMTP',
        'mandatory' => false,
        'condition' => strlen(ini_get('SMTP'))>0,
        'by' => 'Email sending',
        'memo' => 'PHP mail SMTP server required',
    ),
    // Inotify
    'peclInotify' => array(
        'name' => 'PECL Infotify',
        'mandatory' => true,
        'condition' => extension_loaded('inotify'),
        'by' => 'Server-Sent Events',
        'memo' => 'Realtime events in views',
    ),
    'phpFileUploads' => array(
        'name' => 'PHP allow file uploads',
        'mandatory' => true,
        'condition' => $requirementsChecker->checkPhpIniOn("file_uploads"),
        'by' => 'File upload',
        'memo' => '<code>file_uploads</code> should be enabled at <code>php.ini</code>',
    ),    
    'phpUploadMaxFilesize' => array(
        'name' => 'PHP upload max filesize',
        'mandatory' => true,
        'condition' => $requirementsChecker->checkUploadMaxFileSize("128M", null),
        'by' => 'File upload',
        'memo' => '<code>upload_max_filesize</code> should be set at least to 128M at <code>php.ini</code> (current value: ' . ini_get('upload_max_filesize') . ')',
    ),
    'phpPostMaxSize' => array(
        'name' => 'PHP post max size',
        'mandatory' => true,
        'condition' => $requirementsChecker->compareByteSize(ini_get('post_max_size'), "128M"),
        'by' => 'File upload',
        'memo' => '<code>post_max_size</code> should be set at least to 128M at <code>php.ini</code> (current value: ' . ini_get('post_max_size') . ')',
    ),
    'rdiffBackup' => array(
        'name' => 'Rdiff-backup',
        'mandatory' => true,
        'condition' => is_executable('/usr/bin/rdiff-backup'),
        'by' => 'Backup and Restore daemon',
        'memo' => 'Please install the <a href="http://rdiff-backup.nongnu.org/">rdiff-backup</a> package.'
    ),
    'squashfsTools' => array(
        'name' => 'Squashfs',
        'mandatory' => true,
        'condition' => is_executable('/usr/bin/unsquashfs'),
        'by' => 'Squashfs',
        'memo' => 'Please install the <a href="http://squashfs.sourceforge.net/">squashfs-tools</a> package.'
    ),
    'openssh' => array(
        'name' => 'OpenSSH client and key generator',
        'mandatory' => true,
        'condition' => is_executable('/usr/bin/ssh') && is_executable('/usr/bin/ssh-keygen'),
        'by' => 'SSH',
        'memo' => 'Please install the <a href="https://www.openssh.com/">OpenSSH</a> package.'
    ),
    'GD' => array(
        'name' => 'PHP GD extension',
        'mandatory' => true,
        'condition' => extension_loaded('gd'),
        'by' => 'Backup and Restore daemon',
        'memo' => 'Please install the <a href="http://php.net/manual/en/book.image.php">PHP GD</a> extension.',
    ),
    'ZIP' => array(
        'name' => 'zip',
        'mandatory' => true,
        'condition' => extension_loaded('zip'),
        'by' => 'Generate results',
        'memo' => 'Please install the <a href="http://php.net/manual/en/book.zip.php">PHP Zip</a> extension.',
    ),
    'LDAP' => array(
        'name' => 'ldap',
        'mandatory' => false,
        'condition' => extension_loaded('ldap'),
        'by' => 'LDAP/Active Directory Authentication',
        'memo' => 'Please install the <a href="https://www.php.net/manual/de/book.ldap.php">PHP LDAP</a> extension.',
    ),
);
$requirementsChecker->checkYii()->check($requirements)->render();
