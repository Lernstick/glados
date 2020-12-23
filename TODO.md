TODO:

* device information
  * retrieve device information via script about the client
  * such as:
    * CPU/RAM/HDD usage
    * I/O usage
    * network bandwidth
    * connection details/type (lan, wlan, card, ...)
    * device details (dmidecode, lspci, ...)
    * systemd-detect-virt — Detect execution in a virtualized environment
  * store in table ticket_properties similar to exam_settings
  * show in the ticket view

* screen capturing
  * run fetching not every backup interval, only when traffic allows
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
  * create squashfs from running ticket

* live overview
  * new URL in avahi service file
     * <txt-record>actionLive='glados/index.php/ticket/live/{token}'</txt-record>

* locking
  * locking screen in markdown
  * lock keyboard and mouse

* misc
  * "new activity; click to reload" on all tabs in the ticket view
  * move expert settings to the client
  * models/Daemon.php rules -> maybe truncate a too long state/description
  * generally: remove save(false); statements, they cause crashes
  * put .Keylogger path to exclude_list in backupController
  * welcome to exam message in wxbrowser
  * Version conflict in log/history/activities

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
