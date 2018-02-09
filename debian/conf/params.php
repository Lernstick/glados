<?php
return [
    'version' =>                '1.0.3',
    'itemsPerPage' =>           10,
    'tokenLength' =>            10,
    'uploadPath' =>             '/var/lib/glados/uploads/',
    'backupPath' =>             '/var/lib/glados/backups/',
    'resultPath' =>             '/var/lib/glados/results/',
    'tmpPath' =>                '/var/lib/glados/tmp/',
    'dotSSH' =>                 '/var/lib/glados/.ssh/',
    'daemonLogFilePath' =>      '/var/log/glados',
    'examDownloadBandwith' =>   '10m', // 10MB per second, set 0 for no limit
    'minDaemons' =>             3,
    'maxDaemons' =>             10,
    'upperBound' =>             80,
    'lowerBound' =>             20,
];