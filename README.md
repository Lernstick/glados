# Glados

## Installation

Package dependencies

```shell
apt-get install apache2 php mysql-server php-dev composer avahi-daemon
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

Install the packages from `coMposer.json`:
```shell
composer update
```

Install PECL inotify:
```shell
pecl install inotify	   //for PHP7
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
   <txt-record>actionDownload='glados/index.php?r=ticket/download&amp;token={token}'</txt-record>
   <txt-record>actionFinish='glados/index.php?r=ticket/finish&amp;token={token}'</txt-record>
   <txt-record>actionNotify='glados/index.php?r=ticket/notify&amp;token={token}&amp;state={state}'</txt-record>
   <txt-record>actionSSHKey='glados/index.php?r=ticket/ssh-key'</txt-record>
   <txt-record>actionMd5='glados/index.php?r=ticket/md5&amp;token={token}'</txt-record>
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

Hit `http://localhost/glados/requirements.php` to check if all requirements are met.

```shell
:wq
```
