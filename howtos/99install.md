## Installation Guide

Package dependencies

```shell
apt-get install apache2 php5 mysql-server php5-dev avahi-daemon squashfs-tools curl php5-cli rdiff-backup openssh-client
```

Install composer:

```shell
cd /usr/src
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

Create a database and a user

```shell
mysql -u root -p
Enter password:
mysql> create database exam;
mysql> grant usage on *.* to exam@localhost identified by 'exampassword';
mysql> grant all privileges on exam.* to exam@localhost;
mysql> quit
```

Edit `/usr/share/glados/config/db.php` and provide the databasename, the username and the password. This is an example with databasename 'exam', username 'exam' and password 'mysqlpassword':

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=exam',
    'username' => 'exam',
    'password' => 'mysqlpassword',
    'charset' => 'utf8',
];
```

Go to directory `/usr/share/glados`.
RBAC tables:
```shell
./yii migrate --migrationPath=@yii/rbac/migrations
```

RBAC initialization:
```shell
./yii rbac/init
```

Exam db tables:
```shell
./yii migrate
```

Composer asset plugin:
```shell
composer global require "fxp/composer-asset-plugin:^1.2.0"
```

Install the packages from `composer.json`:
```shell
composer update
```

Install PECL inotify:
```shell
pecl install inotify     //for PHP7
pecl install inotify-0.1.6 //for PHP5
```

Create PHP ini file for inotify (`/etc/php/7.0/mods-available/inotify.ini`) with contents:
```ini
; configuration for php inotify module
; priority=20
extension=inotify.so
```

Enable PECL inotify:
```shell
phpenmod inotify
service apache2 restart
```

Create an Avahi service file (`/etc/avahi/services/glados.service`) with contents:
```xml
<?xml version="1.0" standalone='no'?>
<!DOCTYPE service-group SYSTEM "avahi-service.dtd">
<service-group>
 <name replace-wildcards="yes">Glados on %h</name>
  <service>
   <type>_http._tcp</type>
   <port>80</port>
   <txt-record>actionDownload='glados/index.php/ticket/download/{token}'</txt-record>
   <txt-record>actionFinish='glados/index.php/ticket/finish/{token}'</txt-record>
   <txt-record>actionNotify='glados/index.php/ticket/notify/{token}?state={state}'</txt-record>
   <txt-record>actionSSHKey='glados/index.php/ticket/ssh-key'</txt-record>
   <txt-record>actionMd5='glados/index.php/ticket/md5/{token}'</txt-record>
   <txt-record>actionConfig='glados/index.php/ticket/config/{token}'</txt-record>
  </service>
</service-group>
```

Create the Apache config file (`/etc/apache2/conf-available/glados.conf`) with contents:
```
Alias /glados /usr/share/glados/web

<Directory /usr/share/glados/web>
    Options FollowSymLinks
    DirectoryIndex index.php

    # use mod_rewrite for pretty URL support
    RewriteEngine on
    # If a directory or a file exists, use the request directly
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    # Otherwise forward the request to index.php
    RewriteRule . index.php

    # ...other settings...
</Directory>
```

Make sure mod rewrite is enabled and installed:

```shell
a2enmod rewrite
```

Enable apache config:
```shell
a2enconf glados
```

Restart apache:
```shell
service apache2 restart
```

Hit `http://localhost/glados/requirements.php` to check if all requirements are met. Make sure you have set `upload_max_filesize` and `post_max_size` to a proper value in `php.ini`.

After checking all requirements, you can remove the `requirements.php` file.

```shell
:wq
```
