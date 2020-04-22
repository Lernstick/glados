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
  * remove video files when storage space exceeds (overflow)
  * run fetching not every backup interval, only when traffic allows
  * for last backup: fetch must run
  * restore \*.m3u8 and \*.log files

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
  * "longtext" in event table for data field - should be able to contain images in base64