## Upgrade from Debian 9 to 10

This guide describes how to upgrade GLaDOS from Debian 9.x to Debian 10.x with all data mirgated. The old Debian 9 server will be called `old9` and the new Debian 10 server will be called `new10` throughout the whole guide.

----

### Preliminaries

> These step have to be done on `old9`.

Fist, make a backup of all data on the Debian 9 server.

Stop all running daemons via the webinterface.

Upgrade GLaDOS on your server running Debian 9 to version `1.0.11` (see [Softwareupdate on Debian](deb-update.md)).

Create a dump of the MariaDB-database of GLaDOS:

    mysqldump -u 'glados_user' -p 'glados_database' > /tmp/glados_db_1.0.11.sql

Notice that, `glados_user` and `glados_database` must be adjusted according to your setup. You can find the username and database name in the file `/etc/glados/config-db.php`. The contents of that file could look for example:

```php
<?php
##
## database access settings in php format
## automatically generated from /etc/dbconfig-common/glados.conf
## by /usr/sbin/dbconfig-generate-include
## Tue, 31 Jul 2018 08:47:43 +0200
##
## by default this file is managed via ucf, so you shouldn't have to
## worry about manual changes being silently discarded.  *however*,
## you'll probably also want to edit the configuration file mentioned
## above too.
##
$dbuser='user';
$dbpass='secret';
$basepath='';
$dbname='glados';
$dbserver='';
$dbport='';
$dbtype='mysql';
```

In the above case you would have to run the following command:

    mysqldump -u 'user' -p 'glados' > /tmp/glados_db_1.0.11.sql

There will be a prompt asking for the password. In this case the requested password is `secret` (without the single quotes).

Copy the file `/tmp/glados_db_1.0.11.sql` somewhere you have access to for later.

### Setup of the new Debian 10 server

> These step have to be done on `new10`.

On the new Debian 10 server, install a fresh version of GLaDOS `1.0.11` (see [GitHub releases](https://github.com/imedias/glados/releases/tag/1.0.11)) according to the [Installation Guide](deb-install.md). It is important that you install exactly GLaDOS version **`1.0.11`** and not another one. Note down the **password** you have provided during the installation for later use.

### Data migration (files)

> These step have to be done on `new10`.

Once it's installed, you can start copying the files from the old server to the new one. Directories you have to copy:

* `/var/lib/glados/uploads`
* `/var/lib/glados/backups`
* `/var/lib/glados/results`
* `/var/lib/glados/sc`
* `/var/lib/glados/.ssh`
* `/var/log/glados`

If you have changed these directories you have to copy the directories configured in `/etc/glados/params.php` (see [Glados config files](config-files.md) for more details), namely:

* `uploadPath`
* `backupPath`
* `resultPath`
* `scPath`
* `dotSSH`
* `daemonLogFilePath`

You can use `rsync` to transfer the files (the `/` at the end of the directory name is important!):

    rsync -arvpgot -e ssh --delete root@old9:/var/lib/glados/uploads/ /var/lib/glados/uploads
    rsync -arvpgot -e ssh --delete root@old9:/var/lib/glados/backups/ /var/lib/glados/backups
    rsync -arvpgot -e ssh --delete root@old9:/var/lib/glados/results/ /var/lib/glados/results
    rsync -arvpgot -e ssh --delete root@old9:/var/lib/glados/sc/ /var/lib/glados/sc
    rsync -arvpgot -e ssh --delete root@old9:/var/lib/glados/.ssh/ /var/lib/glados/.ssh
    rsync -arvpgot -e ssh --delete root@old9:/var/log/glados/ /var/log/glados

> You can also copy the data with another method, but make sure that the **permission, ownership, group and the modification time** of the files are preserved (the above `rsync` commands do take this into account). The `--delete` flag deletes extraneous files from destination directories on `new10`.

### Data migration (config)

> These step have to be done on `new10`.

If you included authentication methods, such as Active Directory authentication, copy the configuration of the authentication methods to the new system:

    scp -p root@old9:/etc/glados/auth*.php /etc/glados/

### Data migration (database)

> These step have to be done on `new10`.

Copy the database dump file from the old Debian 9 server to the `/tmp` directory of the new Debian 10 server:

    scp -p root@old9:/tmp/glados_db_1.0.11.sql /tmp

Connect to the database server:

    mysql -u glados -p

Provide the **password** you have noted down during the installation here.

In the terminal, you should now see a prompt like

    MariaDB [(none)]>

Switch to the glados database by issuing

    use glados;

The dumped database from earlier in this guide can now be restored by

    source /tmp/glados_db_1.0.11.sql;

Close the console by

    quit

### Data migration (LDAPS)

If you use LDAP authentication with SSL (see [LDAP with SSL](ldap-ssl.md)), you may have to import the LDAP servers CA certificate into the certificate store of the new server. To import all additional certificates from the old server to the new one, run the following command on the new server:

    scp -p root@old9:/usr/local/share/ca-certificates/*.crt /usr/local/share/ca-certificates/

### Upgrade of the new server

You are now ready to update GLaDOS on the new Debian 10 server to the newest version according to the Guide on [Softwareupdate on Debian](deb-update.md).