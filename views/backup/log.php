<?php

if (empty($log)){
	echo \Yii::t('tickets', 'No log file found.');
} else {
	foreach ($log as $logEntry) {
		echo '<samp>' . $logEntry . '</samp><br>';
	}
}

?>
