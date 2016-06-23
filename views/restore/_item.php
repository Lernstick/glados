<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $model app\models\Restore the data model */
/* @var $key integer mixed, the key value associated with the data item */
/* @var $index integer integer, the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget yii\widgets\ListView this widget instance */

?>

<div class="panel-heading">
	<h4 class="panel-title">
    	<a data-toggle="collapse" data-parent="#restores-accordion" href="#restores-collapse<?= $index + 1 ?>">
        	Restore #<?= $widget->dataProvider->totalCount - $key . ' - ' . yii::$app->formatter->format($model->finishedAt, 'relativeTime')
        	 . ' @ ' . yii::$app->formatter->format($model->finishedAt, 'datetime'); ?>
		</a>
    </h4>
</div>

<div id="restores-collapse<?= $index + 1 ?>" class="panel-collapse collapse<?= $index == 0 ? ' in':null ?>">
    <div class="panel-body">
    	<?= DetailView::widget([
        	'model' => $model,
        	'attributes' => [
        		'elapsedTime:duration',
                'startedAt:relativeTime',
                'finishedAt:relativeTime',
                'restoreDate:dateTime',
                'file:text',
        	],
    	]) ?>
	</div>
</div>
