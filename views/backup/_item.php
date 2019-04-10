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
        	<?= \Yii::t('tickets', 'Backup') ?> #<?= $widget->dataProvider->totalCount - $key . ' - ' . yii::$app->formatter->format(intval($model->endTime), 'timeago')
        	 . ' (' . yii::$app->formatter->format($model->totalDestinationSizeChange, 'shortSize') . ' / ' . yii::$app->formatter->format($model->errors, 'integer') . ' errors) '; ?>
		</a>
        <div class="pull-right">
            <div class="btn-group">
              <a href="#" class="dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="glyphicon glyphicon-list-alt"></span> <?= \Yii::t('tickets', 'Actions') ?><span class="caret"></span>
              </a>            
              <ul class="dropdown-menu dropdown-menu-right">
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-paperclip"></span> '. \Yii::t('tickets', 'Show Log File'),
                        Url::to([
                            'backup/log',
                            'ticket_id' => $model->ticket->id,
                            'date' => $model->date
                        ]),
                        [
                            'id' => 'backup-log-show' . $key,
                            'title' => \Yii::t('tickets', 'Show backup log')
                        ]
                    ); ?>
                </li>
                <li class="divider"></li>
                <li>
                    <a class="dropdown-item" href="#"><span class="glyphicon glyphicon-file"></span> <?= \Yii::t('tickets', 'Create Exam from this backup') ?> (TODO)</a>
                </li>                
                <li>
                    <a class="dropdown-item" href="#"><span class="glyphicon glyphicon-file"></span> <?= \Yii::t('tickets', 'Create ZIP File') ?> (TODO)</a>
                </li>
                <li>
                    <a class="dropdown-item" href="#"><span class="glyphicon glyphicon-file"></span> <?= \Yii::t('tickets', 'Create Squash Filesystem') ?> (TODO)</a>
                </li>
              </ul>
            </div>
        </div>
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
                'errors:integer',
        	],
    	]) ?>
	</div>
</div>

<?php

$backupLogButton = "
	$('#backup-log-show" . $key . "').click(function(event) {
		event.preventDefault();
    	$('#backupLogModal').modal('show');
    	$.pjax({url: this.href, container: '#backupLogModalContent', push: false, async:false})
	});
";
$this->registerJs($backupLogButton);

?>
