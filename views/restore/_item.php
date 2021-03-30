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
        	<?= \Yii::t('ticket', 'Restore') ?> #<?= $key . ' - ' . yii::$app->formatter->format($model->finishedAt, 'timeago') ?>
		</a>
        <div class="pull-right">
            <div class="btn-group">
              <a href="#" class="dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="glyphicon glyphicon-list-alt"></span> <?= \Yii::t('ticket', 'Actions') ?><span class="caret"></span>
              </a>            
              <ul class="dropdown-menu dropdown-menu-right">
                <li>
                     <?= Html::a(
                        '<span class="glyphicon glyphicon-paperclip"></span> '. \Yii::t('ticket', 'Show Log File'),
                        Url::to([
                            'log/view',
                            'token' => $model->ticket->token,
                            'date' => date('c', strtotime($model->startedAt)),
                            'type' => 'restore',
                        ]),
                        [
                            'id' => 'restore-log-show' . $key,
                            'title' => \Yii::t('ticket', 'Show restore log')
                        ]
                    ); ?>
                </li>
              </ul>
            </div>
        </div>
    </h4>
</div>

<div id="restores-collapse<?= $index + 1 ?>" class="panel-collapse collapse<?= $index == 0 ? ' in':null ?>">
    <div class="panel-body">
    	<?= DetailView::widget([
        	'model' => $model,
        	'attributes' => [
        		'elapsedTime:duration',
                'startedAt:timeago',
                'finishedAt:timeago',
                'restoreDate:dateTime',
                'file:text',
        	],
    	]) ?>
	</div>
</div>

<?php

$restoreLogButton = "
    $('#restore-log-show" . $key . "').click(function(event) {
        event.preventDefault();
        $('#restoreLogModal').modal('show');
        $.pjax({url: this.href, container: '#restoreLogModalContent', push: false, async:false})
    });
";
$this->registerJs($restoreLogButton);

?>