<?php

if (empty($log)){
	echo \Yii::t('ticket', 'No log file found.');
} else {
	foreach ($log as $logEntry) {
		echo '<samp>' . $logEntry . '</samp><br>';
	}
}

?>
