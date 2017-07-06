<?php

return [
    'version' => '1.0',
    'adminEmail' => 'admin@example.com',
    'itemsPerPage' => 10,
    'ticketExpireTime' => 900,
    'tokenLength' => 10,
    'uploadPath' => '/var/lib/glados/uploads/',
    'backupPath' => '/var/lib/glados/backups/',
    'resultPath' => '/var/lib/glados/results/',
    'examDownloadBandwith' => 20 * 1024 * 1024, // 20MB per second, set 0 for no limit
    'concurrentExamDownloads' => 10, // set 0 for no limit
    'minDaemons' => 3,
    'maxDaemons' => 10,
    'upperBound' => 80,
    'lowerBound' => 20,
];
