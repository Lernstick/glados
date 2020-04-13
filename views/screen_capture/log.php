<?php

if (empty($log)){
    echo \Yii::t('ticket', 'No log file found.');
} else {
    foreach ($log as $logEntry) {
        if (strpos($logEntry, '[info]') !== false) {
            echo '<samp>' . $logEntry . '</samp><br>';
        } else if (strpos($logEntry, '[error]') !== false
            || strpos($logEntry, '[fatal]') !== false
            || strpos($logEntry, '[panic]') !== false
        ) {
            echo '<samp class="text-danger">' . $logEntry . '</samp><br>';
        } else if (strpos($logEntry, '[warning]') !== false) {
            echo '<samp class="text-warning">' . $logEntry . '</samp><br>';
        } else {
            echo '<samp>' . $logEntry . '</samp><br>';
        }
    }
}

?>