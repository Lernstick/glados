## Glados config files

The main configuration can be found in the file `config/params.php`. This is a list of the config settings and what they mean:

Config Item             | Default Value              | Description
------------            | -------------              | -------------
`itemsPerPage`          | `10`                       | Defines the number of rows displayed in the ticket, exam and user index view.
`tokenLength`           | `10`                       | The number of characters in a generated token.
`uploadPath`            | `/var/lib/glados/uploads/` | The full directory path in the filesystem of the server, where the uploaded exam image files should be stored. This directory must be writable for the webserver user.
`backupPath`            | `/var/lib/glados/backups/` | The full directory path in the filesystem of the server, where the backups should be stored. This directory must be writable for the webserver user.
`resultPath`            | `/var/lib/glados/results/` | The full directory path in the filesystem of the server, where the handed back results should be stored. This directory must be writable for the webserver user.
`tmpPath`               | `/var/lib/glados/tmp/`     | The full directory path in the filesystem of the server, wherein temporary files can be stored. This directory must be writable for the webserver user.
`dotSSH`                | `/var/lib/glados/.ssh/`    | The full directory path in the filesystem of the server, where the generated public and private SSH key are stored. This directory must be writable for the webserver user.
`daemonLogFilePath`     | `/var/log/glados`          | The full directory path in the filesystem of the server, where the log files should be stored. This directory must be writable for the webserver user.
`examDownloadBandwith`  | `10m`                      | Given in MB/s (megabytes per second). The bandwith per client, by which the server should transmit the exam files to the client. Set `0` for no limit.
`minDaemons`            | `3`                        | The minimum number of running daemons. If you start one daemon, that daemon will start 2 more after a few seconds to match the minimum number of daemons running.
`maxDaemons`            | `10`                       | The maximum number of running daemons. If you start one more, after some time, one of the daemons will stop probably itself, depending on the bound values (see below).
`lowerBound`            | `20`                       | The average load limit over all running daemons in percent, where one of the running daemons should be stopped (will not go below `minDaemons`).
`upperBound`            | `80`                       | The average load limit over all running daemons in percent, where a new daemons should be started (will not go beyond `maxDaemons`).
`abandonTicket`			| `10800`					 | Time in seconds of failed backup attempts until a ticket is abandoned (no more backups). This value applies only when no other values are set (See *Time Limit* in [Create a ticket](create-single-ticket.md) or in [Create an exam](create-exam.md)). If one of these values is set, the ticket will not be backed up after the *Time Limit* has expired. To be abandoned the ticket must satisfy all the following: <ul><li>Be in the Running state.</li><li>An IP address must be set.</li><li>A *Backup Interval* > 0 must be set.</li><li>If no *Time Limit* (in the ticket or exam) is set, the difference between the last successful backup and the last backup attempt must be geather than the configured <code>abandonTicket</code> time.</li><li>If a *Time Limit* is set (in the ticket or exam, if both are set the one from the ticket will be taken), the difference between the last successful backup and the last backup attempt must be greather than that *Time Limit*.</li></ul>

----

The database connection can be configured in the file `config/db.php`, which usually looks like this:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=glados',
    'username' => 'glados',
    'password' => 'mysqlpassword',
    'charset' => 'utf8',
];
```

This list describes the meanings of the keys:

Config Item  | Description
------------ | ------------- 
`class`      | This shoud be left as it is `yii\db\Connection`.
`dsn`        | The Data Source Name. Please refer to the [PHP manual](http://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
`username`   | The Database Username.
`password`   | The Database Password.
`charset`    | The charset used for database connection. See [Yii2 manual](http://www.yiiframework.com/doc-2.0/yii-db-connection.html#$charset-detail) for more details.