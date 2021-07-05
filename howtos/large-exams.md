## Large exams with 200+ clients

If you want to perform large exams with for example 200+ cuncurrent clients, there are various settings regarding the webserver and database server that have to be increased. In the following, we assume a goal of `(number of concurrent exams you wish to perform) = 200`.

### Hardware recommendations

See [Hardware Recommendations](hardware-recommendations.md).

### GLaDOS

In order for GLaDOS to be able to process 200+ tickets at the same time, you have to increase the setting limiting the maximum number of running daemons under `System->Settings`.

> The value `n` you should choose can be deduced roughly by the formula `n = (number of concurrent exams you wish to perform)/5 + 10`.

### System

Increase the upper limit of the total number of inotify instances per user. You can set this on the fly by

```
echo 8192 >/proc/sys/fs/inotify/max_user_instances
```

Although this will be set back to the default value after the next reboot. To make it permanent, create a new file `/etc/sysctl.d/10-glados.conf` with contents

```
# total number of inotify instances per user
fs.inotify.max_user_instances = 8192
```

> The value `n` you should choose can be deduced roughly by the formula `n = (number of concurrent exams you wish to perform)*10 + 1000`.

### Apache

The maximum number of concurrent connections to the webserver have to be increased as well. Edit the file `/etc/apache2/mods-enabled/mpm_prefork.conf` and change (or add) the directives

```apache
<IfModule mpm_prefork_module>
[...]
    MaxRequestWorkers         300
    ServerLimit               300
[...]
</IfModule>
```

> The value `n` you should choose can be deduced roughly by the formula `n = (number of concurrent exams you wish to perform) + 100`.

To apply the settings, restart the apache2 service

```bash
service apache2 restart
```

### MySQL/ MariaDB

For the database, there is a setting for the maximum number of concurrent connections as well. Create a file `/etc/mysql/mariadb.conf.d/60-glados.cnf` with contents

```
# maximum number of concurrent connections
max_connections        = 350
```

> The value `n` you should choose can be deduced roughly by the formula `n = (number of concurrent exams you wish to perform) + (maximum number of running daemons) + 100`. For the maximum number of running daemons, see `System->Settings`.

To apply the settings, restart the mariaDB service

```bash
service mariadb restart
```