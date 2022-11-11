## Remote Backup Path

This field in the `Create Exam` wizard is very important. It specifies the directory of the students machine to backup.

The default value is `/home/user` which points to the directory were all user related data is stored. In most cases the default setting will be sufficient.

Though, is it possible to change the value. If - for example - system log files need to be included in the backup (those are stored in `/var/log`), the default value `/home/user` will not be sufficient, since the users home directory does not contain `var/log`. Is this case *Remote Backup Path* should be set to `/`. Notice that, **every** single file on the system would then be backed up regularly. This results in very huge backups and a lot of network traffic, compared to the default path. Therefore only do this if you really need to.

It is also possible to restrict the path even further than `/home/user`. Let's say, we only want to backup the files and directories on the Desktop. *Remote Backup Path* would then be set to `/home/user/Desktop`. Backups will be much smaller then, because a lot of files and directories in the users home directory would not be backed up anymore. But notice, if you do that, the *Screenshots* would not be backed up as well, because they are saved under `/home/user/.Screenshots`, which is not included in `/home/user/Desktop`. Also Libreoffice AutoRecovery information and backup copy would not be backed up (stored in `/home/user/.config`), therefore those options are absurd, when *Remote Backup Path* does not contain them.

> Notice that the directory specified in *Remote Backup Path* must exist on the target system, else the backup process will fail.
