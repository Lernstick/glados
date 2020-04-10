TODO:

* device information
** retrieve device information via script about the client
** suchas:
*** CPU/RAM/HDD usage
*** I/O usage
*** network bandwidth
*** connection details/type (lan, wlan, card, ...)
*** device details (dmidecode, lspci, ...)
** store in table ticket_properties similar to exam_settings
** show in the ticket view

* screen capturing
** remove video files when storage space exceeds (overflow)
** run fetching not every backup interval, only when traffic allows
** for last backup: fetch must run