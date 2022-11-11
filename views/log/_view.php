<?php

/* @var $this yii\web\View */
/* @var $model app\models\Log */

if (empty($model->contents)){
	echo \Yii::t('log', 'The logfile is empty.');
} else {
?>

	<table class='terminal'>

	<?php foreach ($model->formatted_contents as $key => $line) {
		echo substitute("<tr><td class='line_number' data-line-number={loc}></td><td class='line'><samp>{line}</samp></td></tr>", [
			'loc' => $key+1,
			'line' => $line,
		]);
	} ?>


	</table>

<?php } ?>
