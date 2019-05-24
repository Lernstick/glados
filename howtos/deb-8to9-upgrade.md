## Upgrade from Debian 8 to 9

This guide describes how to upgrade GLaDOS from Debian 8.x to Debian 9.x with all data mirgated. The old Debian 8 server will be called `old8` and the new Debian 9 server will be called `new9` throughout the whole guide.

----

### Preliminaries

> These step have to be done on `old8`.

Fist, make a backup of all data on the Debian 8 server.

Stop all running daemons via the webinterface.

Upgrade GLaDOS on your server running Debian 8 to version `1.0.4` (see [Softwareupdate on Debian](deb-update.md)).

Create a dump of the MySQL-database of GLaDOS:

    mysqldump -u 'glados_user' -p 'glados_database' > /tmp/glados_db_1.0.4.sql

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

    mysqldump -u 'user' -p 'glados' > /tmp/glados_db_1.0.4.sql

There will be a prompt asking for the password. In this case the requested password is `secret` (without the single quotes).

Copy the file `/tmp/glados_db_1.0.4.sql` somewhere you have access to for later.

### Setup of the new Debian 9 server

> These step have to be done on `new9`.

On the new Debian 9 server, install a fresh version of GLaDOS `1.0.4` (see [GitHub releases](https://github.com/imedias/glados/releases/tag/1.0.4)) according to the [Installation Guide](deb-install.md). It is important that you install exactly GLaDOS version **`1.0.4`** and not another one. Note down the **password** you have provided during the installation for later use.

### Data migration (files)

> These step have to be done on `new9`.

Once it's installed, you can start copying the files from the old server to the new one. Directories you have to copy:

* `/var/lib/glados/uploads`
* `/var/lib/glados/backups`
* `/var/lib/glados/results`
* `/var/lib/glados/.ssh`
* `/var/log/glados`

If you have changed these directories you have to copy the directories configured in `/etc/glados/params.php` (see [Glados config files](config-files.md) for more details), namely:

* `uploadPath`
* `backupPath`
* `resultPath`
* `dotSSH`
* `daemonLogFilePath`

You can use `rsync` to transfer the files (the `/` at the end of the directory name is important!):

    rsync -arvpgot -e ssh --delete root@old8:/var/lib/glados/uploads/ /var/lib/glados/uploads
    rsync -arvpgot -e ssh --delete root@old8:/var/lib/glados/backups/ /var/lib/glados/backups
    rsync -arvpgot -e ssh --delete root@old8:/var/lib/glados/results/ /var/lib/glados/results
    rsync -arvpgot -e ssh --delete root@old8:/var/lib/glados/.ssh/ /var/lib/glados/.ssh
    rsync -arvpgot -e ssh --delete root@old8:/var/log/glados/ /var/log/glados

> You can also copy the data with another method, but make sure that the **permission, ownership, group and the modification time** of the files are preserved (the above `rsync` commands do take this into account).

### Data migration (database)

> These step have to be done on `new9`.

Copy the database dump file from the old Debian 8 server to the `/tmp` directory of the new Debian 9 server:

    scp -p root@old8:/tmp/glados_db_1.0.4.sql /tmp

Connect to the database server:

    mysql -u glados -p

Provide the **password** you have noted down during the installation here.

In the terminal, you should now see a prompt like

    MariaDB [(none)]>

Switch to the glados database by issuing

    use glados;

The dumped database from earlier in this guide can now be restored by

    source /tmp/glados_db_1.0.4.sql;

Close the console by

    quit

### Upgrade of the new server

You are now ready to update GLaDOS on the new Debian 9 server to the newest version according to the Guide on [Softwareupdate on Debian](deb-update.md).