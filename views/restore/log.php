<?php

if (empty($log)){
	echo "No log file found.";
} else {
	foreach ($log as $logEntry) {
		echo '<samp>' . $logEntry . '</samp><br>';
	}
}

?>
