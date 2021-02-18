<?php

/* @var $this yii\web\View */
/* @var $model app\models\Log */

if (empty($model->contents)){
	echo \Yii::t('log', 'The logfile is empty.');
} else {
	foreach ($model->contents as $line) {
		if ($model->typeOfLine($line) == $model::ENTRY_ERROR) {
			echo '<samp class="bg-danger">' . $line . '</samp><br>';
		} else if ($model->typeOfLine($line) == $model::ENTRY_WARNING) {
			echo '<samp class="bg-warning">' . $line . '</samp><br>';
		} else {
			echo '<samp>' . $line . '</samp><br>';
		}
	}
}

?>
