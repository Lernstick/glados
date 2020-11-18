<?php

/**
 * Please to not edit this file.
 * These params are set (and might get adjusted in new versions) for the case
 * that the values are not set neither in params.php nor in the database. Notice
 * that, values comming from params.php or from the database have precedence to
 * these settings.
 * 
 * @see https://github.com/imedias/glados/blob/master/howtos/config-files.md
 * @return array
 */

return [
    'itemsPerPage' =>           10,
    'uploadPath' =>             '/var/lib/glados/uploads/',
    'backupPath' =>             '/var/lib/glados/backups/',
    'resultPath' =>             '/var/lib/glados/results/',
    'tmpPath' =>                '/var/lib/glados/tmp/',
    'scPath' =>                 '/var/lib/glados/sc/',
    'sciptsPath' =>             '/usr/share/glados/scripts/',
    'dotSSH' =>                 '/var/lib/glados/.ssh/',
    'daemonLogFilePath' =>      '/var/log/glados',
    'examDownloadBandwith' =>   '10m', // 10MB per second, set 0 for no limit
    'minDaemons' =>             3,
    'maxDaemons' =>             10,
    'upperBound' =>             80,
    'lowerBound' =>             20,
    'abandonTicket' =>          10800, // leave the ticket after 3 hours of failed backup attempts
    'liveEvents' =>             true, // enable or disable live data
];
