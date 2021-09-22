<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use app\models\Ticket;
use yii\widgets\Pjax;
use app\components\ActiveEventField;
use yii\bootstrap\Modal;


/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $title string */
/* @var $finish bool */

$this->registerJs('window.location.href = "#wxbrowser:resize:800x600"');

if (isset($title)) {
    echo "<h1>" . Html::encode($title) . "</h1>";

    if ($model->last_backup == 1) {
        $this->registerJs('$("#success").modal("show");');
    }

    echo ActiveEventField::widget([
        'content' => null,
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'last_backup',
        'jsHandler' => 'function(d, s){
            if (d == "1") {
                $("#success").modal("show");
            }
        }',
    ]);

}

ActiveEventField::begin([
    'event' => 'ticket/' . $model->id,
    'jsonSelector' => 'action',
    'jsHandler' => 'function(d, s){if(d == "update"){$.pjax.reload({container: "#status"});}}'
]);
ActiveEventField::end();

Pjax::begin([
    'id' => 'status',
    'options' => ['class' => 'tab-pane fade in active'],
]);

$started = $finished = $submitted = $result = false;
if (file_exists($model->result)) {
    $this->registerJs("$('#pb-res').width('17.5%');");
    $this->registerJs("$('#pb-sub').width('17.5%');");
    $this->registerJs("$('#pb-run').width('65%');");
    echo '<div class="jumbotron">' . 
        '<h1>' . \Yii::t('results', 'Hello {student}', ['student' => ($model->test_taker ? $model->test_taker : $model->token)]) . '</h1>' . 
        '<p>' . \Yii::t('results', 'Your exam result is handed in, check it out!') . '</p>' . 
        '<p>' . 
        Html::a(
            '<span class="glyphicon glyphicon-save-file"></span> ' . \Yii::t('results', 'Download my result'),
            ['result/download', 'token' => $model->token],
            ['data-pjax' => 0, 'class' => 'btn btn-primary btn-lg', 'role' => 'button']
        ) . 
        '</div>';
    $result = true;
}
if ($model->state == Ticket::STATE_CLOSED){
    $this->registerJs("$('#pb-run').width('65%');");
    $started = $finished = true;
} else if ($model->state == Ticket::STATE_SUBMITTED){
    $this->registerJs("$('#pb-sub').width('17.5%');");
    $this->registerJs("$('#pb-run').width('65%');");    
    $started = $finished = $submitted = true;
} else if ($model->state == Ticket::STATE_RUNNING) {
    if (is_object($model->validTime)) {
        $s = date_create('@0')->add($model->validTime)->getTimestamp();
        $p = ($model->limit*60 - $s)/($model->limit*60/100);
        $p *= 0.65;
    } else if ($model->validTime == false) {
        $p = 65;
    } else {
        $p = 25;
    }
    $this->registerJs("$('#pb-run').width('" . $p . "%');");
    $started = true;
}

?>

<div class="progress" style="position:relative;">
  <div class="progress-result">&nbsp;</div>
  <div id="pb-run" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
  </div>
  <div id="pb-sub" class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
  </div>
  <div id="pb-res" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
  </div>    
</div>

<div class="row">
    <div class="col-md-6 col-xs-6">
        <p class="text-left text-<?= !$started ? 'danger' : 'success' ?>">
            <span class="glyphicon glyphicon-triangle-top"></span>
            <?= \Yii::t('results', 'started') ?>
        </p>
    </div>
    <div class="col-md-2 col-xs-2">
        <p class="pull-right text-<?= !$finished ? 'danger' : 'success' ?>">
            <span class="pull-right glyphicon glyphicon-triangle-top"></span>
            <?= \Yii::t('results', 'finished') ?>&nbsp;
        </p>
    </div>        
    <div class="col-md-2 col-xs-2">
        <p class="pull-right text-<?= !$submitted ? 'danger' : 'success' ?>">
            <span class="pull-right glyphicon glyphicon-triangle-top"></span>
            <?= \Yii::t('results', 'submitted') ?>&nbsp;
        </p>
    </div>
    <div class="col-md-2 col-xs-2">
        <p class="pull-right text-<?= !$result ? 'danger' : 'success' ?>">
            <span class="pull-right glyphicon glyphicon-triangle-top"></span>
            <?= \Yii::t('results', 'result handed in') ?>&nbsp;
        </p>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="row">
            <div class="col-xs-8">
                <span><?= \Yii::t('ticket', 'Exam') . ':' . $model->examSubject . " - " . $model->examName; ?></span>
            </div>
            <div class="col-xs-4">
                <?php if (isset($title)) { ?>
                <div class="dropdown pull-right">
                    <a class="btn btn-secondary btn-xs dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="glyphicon glyphicon-list-alt"></i>
                        Actions&nbsp;<span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <?= Html::a(
                                '<span class="glyphicon glyphicon-repeat"></span> Reload this page',
                                ['status', 'token' => $model->token]
                            ) ?>
                        </li>
                        <li>
                            <?= Html::a('<span class="glyphicon glyphicon-flag"></span> ' . \Yii::t('app', 'Hand-in'), '#', [
                                'data-toggle' => 'modal',
                                'data-target' => '#confirm',
                            ]); ?>
                        </li>
                        <li>
                            <?= Html::a(
                                '<span class="glyphicon glyphicon-question-sign"></span> ' . \Yii::t('client', 'Help'),
                                ['howto/view', 'id' => 'welcome-to-exam.md', 'mode' => 'inline'],
                                ['onclick' => 'window.open("' . Url::to(['howto/view', 'id' => 'welcome-to-exam.md', 'mode' => 'inline']) . '", "Help", "titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=800"); return false;']
                            ) ?>
                        </li>
                    </ul>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-8 col-xs-8">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        [
                            'attribute' => 'state',
                            'value' => '<span class="label label-' . (
                                array_key_exists($model->state, $model->classMap) ? $model->classMap[$model->state] : 'default'
                            ) . '">' . yii::$app->formatter->format($model->state, 'state') . '</span>',
                            'format' => 'html',
                        ],
                        'token',
                        'exam.name',
                        'start:timeago',
                        'end:timeago',
                        'duration:duration',
                        'test_taker',
                    ],
                ]); ?>
            </div>

            <div class="col-md-4 col-xs-4">
                <div class="well">
                    <h1 class="text-center">
                    <small>
                    <?php
                        if ($model->state == Ticket::STATE_RUNNING) {
                            if ($model->validTime === false) {
                                echo \Yii::t('results', 'Time is up.');
                            } else if ($model->validTime === true) {
                                echo \Yii::t('results', 'There is no time limit.');
                            } else {
                                $date = new DateTime('now');
                                $date->add($model->validTime);
                                ##$date->modify('+30 seconds');
                                echo \russ666\widgets\Countdown::widget([
                                    'datetime' => $date->format('Y-m-d H:i:s O'),
                                    'format' => \Yii::t('results', 'The exam is over in ...') . '<br> %-N %!N:minute,minutes; %-S %!S:second,seconds;',
                                    'events' => [
                                        'finish' => 'function(){
                                            this.innerHTML = "Time is up.";
                                            end = 65.0;
                                            $("#pb-run").width(end + "%");
                                        }',
                                        'update' => 'function(e){
                                            width = 100*parseFloat($("#pb-run").css("width"))/parseFloat($("#pb-run").parent().css("width"));
                                            end = 65.0;
                                            p = end - e.offset.totalSeconds/((e.offset.totalSeconds + 1)/(end - width));
                                            $("#pb-run").width(p + "%")
                                        }',
                                    ],
                                ]);
                            }
                        } else if ($model->state == Ticket::STATE_OPEN) {
                            echo \Yii::t('results', 'The exam has not started yet.');
                        } else {
                            echo \Yii::t('results', 'The exam is over.');
                        }
                    ?>
                    </small>
                    </h1>
                </div>
            </div>
        </div>
    </div>    
</div>

<?php Pjax::end(); ?>

<?= $this->render('/ticket/_finish_s1', [
    'model' => $model,
    'finish' => isset($finish) && $finish,
]) ?>

<div id="success" class="modal modal-centered fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4><?= \Yii::t('client', 'Continue') ?></h4>
            </div>

            <div class="modal-body clearfix">
                <p><?= \Yii::t('client', 'You exam was handed-in successfully. You can close this window now.') ?></p>
                <div class="pull-right">
                    <?= Html::a(\Yii::t('app', 'Close'), '#wxbrowser:close', ['class' => 'btn btn-default']); ?>
                    <?= Html::a(\Yii::t('app', 'Shutdown'), '#wxbrowser:shutdown', ['class' => 'btn btn-danger']); ?>
                </div>
            </div>

        </div>
    </div>
</div>