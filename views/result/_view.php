<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Ticket;
use yii\widgets\Pjax;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

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
        '<h1>Hello ' . ($model->test_taker ? $model->test_taker : $model->token) . '</h1>' . 
        '<p>Your exam result is handed in, check it out!</p>' . 
        '<p>' . 
        Html::a(
            '<span class="glyphicon glyphicon-save-file"></span> Download my result',
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
    <div class="col-md-7">
        <p class="text-left text-<?= !$started ? 'danger' : 'success' ?>">
            <span class="glyphicon glyphicon-triangle-top"></span> started
        </p>
    </div>
    <div class="col-md-1">
        <p class="text-left text-<?= !$finished ? 'danger' : 'success' ?>">
            finished <span class="glyphicon glyphicon-triangle-top"></span>
        </p>
    </div>        
    <div class="col-md-2">
        <p class="text-right text-<?= !$submitted ? 'danger' : 'success' ?>">
            submitted <span class="glyphicon glyphicon-triangle-top"></span>
        </p>
    </div>
    <div class="col-md-2">
        <p class="text-right text-<?= !$result ? 'danger' : 'success' ?>">
            result handed in <span class="glyphicon glyphicon-triangle-top"></span>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
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
    <div class="col-md-4">
        <div class="well">
            <h1 class="text-center">
            <small>
            <?php
                if ($model->state == Ticket::STATE_RUNNING) {
                    if ($model->validTime === false) {
                        echo "Time is up.";
                    } else if ($model->validTime === true) {
                        echo "There is no time limit.";
                    } else {
                        $date = new DateTime('now');
                        $date->add($model->validTime);
                        ##$date->modify('+30 seconds');
                        echo \russ666\widgets\Countdown::widget([
                            'datetime' => $date->format('Y-m-d H:i:s O'),
                            'format' => 'The world might end in...<br> %-N %!N:minute,minutes; %-S %!S:second,seconds;',
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
                                    console.log(p, width);
                                    $("#pb-run").width(p + "%")
                                }',
                            ],
                        ]);
                    }
                } else if ($model->state == Ticket::STATE_OPEN) {
                    echo "The exam has not started yet.";
                } else {
                    echo "The exam is over.";                    
                }
            ?>
            </small>
            </h1>
        </div>            
    </div>
</div>

<?php Pjax::end(); ?>