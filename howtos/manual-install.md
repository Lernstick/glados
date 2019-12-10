## Installation Guide (From the sources)

This guide describes how to install GLaDOS from the source package.

-----

### Requirements

#### Webserver

Since GLaDOS's interface is based entirely in your browser, you’ll need a web server (such as Apache, nginx) to install the files into.

* Apache needs [mod_rewrite](http://httpd.apache.org/docs/current/mod/mod_rewrite.html) to be installed and enabled.
* Nginx needs PHP as an [FPM SAPI](http://php.net/install.fpm).

#### PHP

* You should have PHP 5.4 or above. Ideally latest PHP 7. You should also install the [PDO PHP Extension](http://www.php.net/manual/en/pdo.installation.php) and the corresponding database driver `pdo_mysql`.
* To support generating of ZIP files, you need the PHP `zip` extension.
* To support live data, you need the [PECL inotify](https://pecl.php.net/package/inotify/0.1.6) extension version 0.1.6.
* To support creating thumbnails, you need the PHP `gd` extension.
* Yii2 needs the Multibyte-Strings [mbstring](http://php.net/manual/de/book.mbstring.php) module for PHP.

#### Database

Currently only MySQL databases are supported. You need MySQL 5.5 or newer.

#### Miscellaneous

* To fetch the exam backups, [rdiff-backup](http://rdiff-backup.nongnu.org/) 1.2.8 or newer is needed.
* [OpenSSH](https://www.openssh.com/) client and its key generator `ssh-keygen` are needed to create a connection for rdiff-backup.
* To support squashfs files, [Squashfs](http://squashfs.sourceforge.net/) is needed.
* To support auto discovery of the exam server, [avahi](http://avahi.org/) is needed.
* For the manual installation [Composer](https://getcomposer.org/download/) is needed. It can be removed subsequently.

#### Debian

In Debian, the packages needed from above can be installed by:

    apt-get install apache2 mysql-server php5 php5-mysql php5-gd squashfs-tools rdiff-backup avahi-daemon openssh-client

### Installation

Composer can be installed to `/usr/local/bin` with the following commands (you need [curl](https://curl.haxx.se/) for this to work):

```shell
cd /usr/src
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

#### Download the latest source package

Browse to the Github [release page](https://github.com/imedias/glados/releases) and download the latest version of GLaDOS.

    curl -L -O https://github.com/imedias/glados/archive/$version.tar.gz

Where `$version` is the latest version number.

Unpack the source package:

    tar xfs $version.tar.gz

Create a new directory `/usr/share/glados` and copy all extracted files. Then `cd` into the created directory:

```shell
mkdir /usr/share/glados
cp -rpv glados-1.0.3/* /usr/share/glados/
cd /usr/share/glados
```

Install composer asset plugin (according to the [Yii2 installation guide](https://github.com/yiisoft/yii2/blob/master/framework/README.md)):

    composer global require "fxp/composer-asset-plugin:^1.4.1"

Run composer (this will take a while, you also may need to create a [Github OAuth token](https://github.com/blog/1509-personal-api-tokens) to go over the [API rate limit](https://getcomposer.org/doc/articles/troubleshooting.md#api-rate-limit-and-oauth-tokens)):

    composer update

This will install all packages specified in `composer.json`.

-----

Create the following directories:

    mkdir -p /var/lib/glados/uploads
    mkdir -p /var/lib/glados/backups
    mkdir -p /var/lib/glados/results
    mkdir -p /var/lib/glados/tmp
    mkdir -p /var/lib/glados/.ssh
    mkdir -p /var/log/glados

Some directories need to be writable by the user under which your webserver runs. Assuming the user is called `www-data`, adjust the following permissions:

    chown www-data /var/log/glados
    chown www-data:www-data /usr/share/glados/web/assets/
    chown -R www-data:www-data /var/lib/glados

Next, set the environment variables. Edit `/usr/share/glados/web/index.php` and comment out lines 4 and 5:

    // comment out the following two lines when deployed to production
    //defined('YII_DEBUG') or define('YII_DEBUG', true);
    //defined('YII_ENV') or define('YII_ENV', 'dev');

#### MySQL setup

Once all dependencies are installed, you need to set up the database. The following example code, shows how to create a database called `glados`, with a database user called `glados` and password `mysqlpassword` with all permissions granted on that database.

```shell
mysql -u root -p
Enter password:
mysql> create database glados;
mysql> grant usage on *.* to glados@localhost identified by 'mysqlpassword';
mysql> grant all privileges on glados.* to glados@localhost;
mysql> quit
```

Open the config file `/usr/share/glados/config/db.php` and provide the database name, the username and the password from above (see [Config Files](config-files.md) for more information):

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=glados',
    'username' => 'glados',
    'password' => 'mysqlpassword',
    'charset' => 'utf8',
];
```

Change into the directory `/usr/share/glados`. Install all RBAC tables:

```shell
./yii migrate --migrationPath=@yii/rbac/migrations --interactive=0
```

RBAC initialization:

```shell
./yii rbac/init --interactive=0
```

Database tables:

```shell
./yii migrate --interactive=0
```

#### PHP setup

Install PECL inotify (you need [PEAR](https://pear.php.net/), PHP5 module development files and a C-compiler for this to work):

```shell
pecl install inotify        //for PHP7
pecl install inotify-0.1.6  //for PHP5
```

Create a PHP ini file for inotify (example: `/etc/php/7.0/mods-available/inotify.ini` for PHP7 and `/etc/php5/mods-available/inotify.ini` for PHP5) with contents:

```ini
; configuration for php inotify module
; priority=20
extension=inotify.so
```

> Make sure to enable the inotify module.

#### Avahi setup

Create an Avahi service file (`/etc/avahi/services/glados.service`) with contents:

```xml
<?xml version="1.0" standalone='no'?>
<!DOCTYPE service-group SYSTEM "avahi-service.dtd">
<service-group>
 <name replace-wildcards="yes">Glados on %h</name>
  <service>
   <type>_http._tcp</type>
   <port>80</port>
   <txt-record>type=Glados</txt-record>
   <txt-record>actionDownload='glados/index.php/ticket/download/{token}'</txt-record>
   <txt-record>actionFinish='glados/index.php/ticket/finish/{token}'</txt-record>
   <txt-record>actionNotify='glados/index.php/ticket/notify/{token}?state={state}'</txt-record>
   <txt-record>actionSSHKey='glados/index.php/ticket/ssh-key'</txt-record>
   <txt-record>actionMd5='glados/index.php/ticket/md5/{token}'</txt-record>
   <txt-record>actionConfig='glados/index.php/ticket/config/{token}'</txt-record>
  </service>
</service-group>
```

Make sure that the file above contains the correct URLs (this depends on your webserver setup, see below). For example the download URL `actionDownload` will be made up of the hosts IP-address, the port, the protocol and the given relative path from the txt-record `actionDownload`, discovered by `avahi-browse`. The protocol is determined by the port number, `80` gives `http` and `443` gives `https`.

```shell
${gladosProto}://${gladosIp}:${gladosPort}/${actionDownload}
```

will then be

```shell
http://1.2.3.4:80/glados/index.php/ticket/download/{token}
```

If you use the Apache setup from below, you don't have to change the service file.

Finally restart the avahi-daemon:

    /etc/init.d/avahi-daemon restart

#### Webserver setup

##### **Apache**

Use the following configuration in [Apache's](https://httpd.apache.org/) `httpd.conf` file or within a virtual host configuration (example: `/etc/apache2/conf-available/glados.conf`).

```apache
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

> Make sure `mod_rewrite` is enabled and installed.

Then restart apache:

```shell
/etc/init.d/apache2 restart
```

##### **Nginx**

To use [Nginx](http://wiki.nginx.org/), you should install PHP as an [FPM SAPI](http://php.net/install.fpm). You may use the following Nginx configuration, replacing `glados.test` with the actual hostname to serve.

```nginx
server {
    charset utf-8;
    client_max_body_size 128M;

    listen 80; ## listen for ipv4
    #listen [::]:80 default_server ipv6only=on; ## listen for ipv6

    server_name glados.test;
    root        /usr/share/glados/web;
    index       index.php;

    access_log  /var/log/glados/access.log;
    error_log   /var/log/glados/error.log;

    location / {
        # Redirect everything that isn't a real file to index.php
        try_files $uri $uri/ /index.php$is_args$args;
    }

    # uncomment to avoid processing of calls to non-existing static files by Yii
    #location ~ \.(js|css|png|jpg|gif|swf|ico|pdf|mov|fla|zip|rar)$ {
    #    try_files $uri =404;
    #}
    #error_page 404 /404.html;

    # deny accessing php files for the /assets directory
    location ~ ^/assets/.*\.php$ {
        deny all;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        #fastcgi_pass 127.0.0.1:9000;
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        try_files $uri =404;
    }

    location ~* /\. {
        deny all;
    }
}
```

When using this configuration, you should also set `cgi.fix_pathinfo=0` in the `php.ini` file in order to avoid many unnecessary system `stat()` calls.

Also note that when running an HTTPS server, you need to add `fastcgi_param HTTPS on;` so that Yii can properly detect if a connection is secure.

#### Check the installation

Hit [http://localhost/glados/requirements.php](http://localhost/glados/requirements.php) to check if all requirements are met.

Make sure you have set `upload_max_filesize` and `post_max_size` to a proper value in `php.ini`.

> After checking all requirements, you can remove the `requirements.php` file located at `/usr/share/glados/web/requirements.php`.

You can now access the webinterface by the URL [http://localhost/glados](http://localhost/glados).

You may login with **admin/admin** or **teacher/teacher**.
To modify the users, please login as **admin**.

```shell
:wq
```
