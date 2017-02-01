<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $model app\models\Backup the data model */
/* @var $key integer mixed, the key value associated with the data item */
/* @var $index integer integer, the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget yii\widgets\ListView this widget instance */

?>

<div class="panel-heading">
	<h4 class="panel-title">
    	<a data-toggle="collapse" data-parent="#backups-accordion" href="#backups-collapse<?= $index + 1 ?>">
        	Backup #<?= $widget->dataProvider->totalCount - $key . ' - ' . yii::$app->formatter->format(intval($model->endTime), 'timeago')
        	 . ' @ ' . yii::$app->formatter->format(intval($model->endTime), 'datetime')
        	 . ' (' . yii::$app->formatter->format($model->totalDestinationSizeChange, 'shortSize') . ' / ' . yii::$app->formatter->format($model->errors, 'integer') . ' errors) '; ?>
		</a>
    </h4>
</div>

<div id="backups-collapse<?= $index + 1 ?>" class="panel-collapse collapse<?= $index == 0 ? ' in':null ?>">
    <div class="panel-body">
    	<?= DetailView::widget([
        	'model' => $model,
        	'attributes' => [
        		'elapsedTime:duration',
        	    [
                	'attribute' => 'sourceFiles',
                	'value' => yii::$app->formatter->format($model->sourceFiles, 'integer')
                		 . ' (' . yii::$app->formatter->format($model->sourceFileSize, 'shortSize') . ')',
	                'format' => 'html',
            	],
        	    [
                	'attribute' => 'mirrorFiles',
                	'value' => yii::$app->formatter->format($model->mirrorFiles, 'integer')
                		 . ' (' . yii::$app->formatter->format($model->mirrorFileSize, 'shortSize') . ')',
	                'format' => 'html',
            	],
        	    [
                	'attribute' => 'deletedFiles',
                	'value' => yii::$app->formatter->format($model->deletedFiles, 'integer')
                		 . ' (' . yii::$app->formatter->format($model->deletedFileSize, 'shortSize') . ')',
	                'format' => 'html',
            	],
        	    [
                	'attribute' => 'changedFiles',
                	'value' => yii::$app->formatter->format($model->changedFiles, 'integer')
                		 . ' (' . yii::$app->formatter->format($model->changedSourceSize, 'shortSize')
                		 . ' / ' . yii::$app->formatter->format($model->changedMirrorSize, 'shortSize') . ')',
	                'format' => 'html',
            	],
        	    [
                	'attribute' => 'incrementFiles',
                	'value' => yii::$app->formatter->format($model->incrementFiles, 'integer')
                		 . ' (' . yii::$app->formatter->format($model->incrementFileSize, 'shortSize') . ')',
	                'format' => 'html',
            	],
            	'totalDestinationSizeChange:shortSize',
        	    [
                	'attribute' => 'errors',
                	'value' => $model->errors == 0 ? '0' : $model->errors . ', ' . Html::a(
    					'<span class="glyphicon glyphicon-modal-window"></span> Show Errors',
    					Url::to(['backup/view-errors', 'ticket_id' => $model->ticket->id, 'date' => $model->date]),
    					['id' => 'errors-show' . $key]
					),
	                'format' => 'raw',
            	],
        	],
    	]) ?>
	</div>
</div>

<?php

if ($model->errors != 0) {
	$backupErrorsButton = "
    	$('#errors-show" . $key . "').click(function(event) {
    		event.preventDefault();
        	$('#errorsModal').modal('show');
        	$.pjax({url: this.href, container: '#errorsModalContent', push: false, async:false})
    	});
	";
	$this->registerJs($backupErrorsButton);
}

?>
