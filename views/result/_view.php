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

$js = <<< 'SCRIPT'
$('#confirmFinish').on('show.bs.modal', function(e) {
    //$(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
    //console.log($(e.relatedTarget).data('href'));
});

$(document).on('click', '#confirmFinish a#finish-now', function (e) {
  //e.stopPropagation();
  $('#confirmFinish').modal('hide');
  $.ajax({
    url: "/glados/index.php/ticket/finish/1234",
    type: "GET",
    success: function(data){
        if (data.code != 200) {
            alert("Error " + data.code + ": " + data.msg);
        }
    },
    error: function(error){
         console.log("Error:");
         console.log(error);
    }
  });

});
SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);

if (isset($title)) {
    echo "<h1>" . Html::encode($title) . "</h1>";
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
    <div class="col-md-7 col-xs-7">
        <p class="text-left text-<?= !$started ? 'danger' : 'success' ?>">
            <span class="glyphicon glyphicon-triangle-top"></span><br>started
        </p>
    </div>
    <div class="col-md-1 col-xs-1">
        <p class="text-right text-<?= !$finished ? 'danger' : 'success' ?>">
            <span class="glyphicon glyphicon-triangle-top"></span><br>finished
        </p>
    </div>        
    <div class="col-md-2 col-xs-2">
        <p class="text-right text-<?= !$submitted ? 'danger' : 'success' ?>">
            <span class="glyphicon glyphicon-triangle-top"></span><br>submitted
        </p>
    </div>
    <div class="col-md-2 col-xs-2">
        <p class="text-right text-<?= !$result ? 'danger' : 'success' ?>">
            <span class="glyphicon glyphicon-triangle-top"></span><br>result handed in
        </p>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="row">
            <div class="col-xs-8">
                <span>Exam: <?= $model->examSubject . " - " . $model->examName; ?></span>
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
                            <?= Html::a(
                                '<span class="glyphicon glyphicon-flag"></span> Hand-in Exam',
                                Url::to([
                                    'ticket/finish',
                                    'token' => $model->token,
                                ]),
                                [
                                    'data-href' => Url::to([
                                        'ticket/finish',
                                        'token' => $model->token,
                                    ]),
                                    'data-toggle' => 'modal',
                                    'data-target' => '#confirmFinish',
                                ]
                            ) ?>
                        </li>
                        <li>
                            <?= Html::a(
                                '<span class="glyphicon glyphicon-question-sign"></span> Help',
                                ['howto/view', 'id' => 'welcome-to-exam.md', 'mode' => 'inline'],
                                ['onclick' => 'window.open("' . Url::to(['howto/view', 'id' => 'welcome-to-exam.md', 'mode' => 'inline']) . '", "Help", "titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=900,height=800"); return false;']
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
            <div class="col-md-8 col-xs-12">
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
                        //'exam.name',
                        'start:timeago',
                        'end:timeago',
                        'duration:duration',
                        'test_taker',
                        'backup_state',
                    ],
                ]); ?>
            </div>
            <div class="col-md-4 col-xs-12">
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

    </div>    
</div>

<?php Pjax::end(); ?>

<?php Modal::begin([
    'id' => 'confirmFinish',
    'header' => '<h4>Confirm Exam Hand-in</h4>',
    'footer' => Html::Button('Cancel', ['data-dismiss' => 'modal', 'class' => 'btn btn-default']) . '<a id="finish-now" class="btn btn-danger btn-ok">Yes, hand-in my exam</a>',
]); ?>

<p>You're about to hand-in your exam.</p>

<div class="alert alert-danger" role="alert">
  <h4>Important!</h4>

  <p>Please notice, that once this process is initiated, you <b>cannot continue</b> with the exam. So, please make sure you <b>saved all documents</b> and <b>closed all open windows</b> before finishing your exam.</p>
</div>

<?php Modal::end(); ?>