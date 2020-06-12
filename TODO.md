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
  * for last backup: fetch must run
  * restore \*.m3u8 and \*.log files
    * No: the stream should not resume, instead start from new with video2.m3u8, ... done
    * keylogger: old keylogger.key files should be transmitted after a while, even if no more files appear: done
    * launch script should launch files when too old: done
  * VideoJs Plugin: overlay of a 2nd video possibility / for example webcam overlay
    * see commit https://github.com/imedias/glados/commit/c650c33cedde9b410ab965a883b8086c88fd2280
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


* Live overview
  * new URL in avahi service file
     * <txt-record>actionLive='glados/index.php/ticket/live/{token}'</txt-record>

* misc
  * disable firewall not working anymore
  
* daemons
  * have a look at https://github.com/yiisoft/yii2-queue
