TODO:

* device information
  * retrieve device information via script about the client
  * such as:
    * CPU/RAM/HDD usage
    * I/O usage
    * network bandwidth
    * connection details/type (lan, wlan, card, ...)
    * device details (dmidecode, lspci, ...)
  * store in table ticket_properties similar to exam_settings
  * show in the ticket view

* screen capturing
  * remove video files when storage space exceeds (overflow): done
  * run fetching not every backup interval, only when traffic allows
  * for last backup: fetch must run: done
  * when service ends -> launch all files: done
  * restore \*.m3u8 and \*.log files
    * No: the stream should not resume, instead start from new with video2.m3u8, ... done
    * keylogger: old keylogger.key files should be transmitted after a while, even if no more files appear: done
    * launch script should launch files when too old: done
  * VideoJs Plugin: overlay of a 2nd video possibility / for example webcam overlay
    * see commits https://github.com/imedias/glados/commit/c650c33cedde9b410ab965a883b8086c88fd2280 and https://github.com/imedias/glados/commit/da2553bd15ec4beb45638cc63ea536300667d5ae
    * sync the 2 videos
    * simultan play, pause, seek, ...
    * subtitles always in the main window
    * move the PIP
    * resize PIP
    * Button: enable/disable PIP (just as the subtitles)
      * test in fullscreen
    * Button: switch the two videos
      * test in fullscreen

* multiple filessystems for exams
  * option to upload squashfs/zip files detached from exam context
     * name field (ex: "Brackets v1.14.2")
     * description field (markdown) (ex: "Installation of Brackets software")
     * visibility for other teachers checkbox
  * in exam edit view: possibility to choose multiple of these files by the name field (as an exam setting that can be selected multiple times, as much as overlayfs allows)
  * order of mount?
  * in the download process, all these files are rsync to the client
  * in the mount process, these files are mounted/unzipped in the given order before that "main" squashfs/zip files are mounted over them
  
* squashfs
  * place version inside squashfs and check against lernstick version (also check if it's the exam version or standard version)

* Screenshots
  * Own service via systemd

* Live overview
  * new URL in avahi service file
     * <txt-record>actionLive='glados/index.php/ticket/live/{token}'</txt-record>
  * manual for monitoring monitoring-exams.md: done
  * not reloading correctly (events), when initially there was no ticket running: done

* misc
  * Undefined index: scPath: done
  * lernstick-exam-client dependency 1.0.12, not 11: done
  * translations: done
  * update README.md: done
  * disable firewall not working anymore: done
  * backup browse view broken: done
  * backup browse view broken: http://192.168.0.17/glados/index.php/ticket/2941?path=%2FDokumente&date=all&showDotFiles=0#tab_browse: done
  * version check (of lernstick and lernstick-exam-client) in search script: done
  * version check looking at the base system (not persistent): done
  * version check looking at grub config string to distinguish exam/non-exam: done
  * "new activity; chlick to reload" on all tabs in the ticket view
  * move expert settings to the client
  * models/Daemon.php rules -> maybe truncate a too long state/description
  * generally: remove save(false); statements, they cause crashes
  * put .Keylogger oath to exclude_list in backupController

* daemons
  * remove onStart and onStop from ActiveEventField (only used in /views/daemon/index.php)
  * refactor daemon view
  * have a look at https://github.com/yiisoft/yii2-queue
  * use mutex to accuire a lock (locking also works via DB), see https://www.yiiframework.com/doc/api/2.0/yii-mutex-mysqlmutex
    * mysqlMutex uses mysql db to get a lock "SELECT GET_LOCK(name)", see https://mariadb.com/kb/en/get_lock/
    * fileMutex uses flock(), but it resticted to being local (same machine)
    * needs component config in console.php (see https://www.yiiframework.com/doc/api/2.0/yii-mutex-mysqlmutex)
    * remove fields from table ticket:
      * backup_lock
      * restore_lock
      * download_lock
    * getNextItem() in daemons should then not query ->one(), instead:
      * query->all()
      * loop through models and try to accuire lock via Yii::$app->mutex->acquire('nameOfLock')
      * return first model that evaluates true
      * if all models evaluate to false -> return null
