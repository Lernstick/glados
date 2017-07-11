<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Ticket;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

if (file_exists($model->result)) {
    $this->registerJs("document.getElementById('progressbar').style = 'width:100%';");
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
} else if ($model->state == Ticket::STATE_CLOSED){
    $this->registerJs("document.getElementById('progressbar').style = 'width:65%';");    
} else if ($model->state == Ticket::STATE_SUBMITTED){
    $this->registerJs("document.getElementById('progressbar').style = 'width:82.5%';");
} else if ($model->state == Ticket::STATE_RUNNING) {
    $this->registerJs("document.getElementById('progressbar').style = 'width:25%';");
}

?>

<div class="progress" style="position:relative;">
  <div class="progress-result">&nbsp;</div>
  <div id="progressbar" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
  </div>
</div>

    <div class="row">
        <div class="col-md-7">
            <p class="text-left"><span class="glyphicon glyphicon-triangle-top"></span> started</p>
        </div>
        <div class="col-md-1">
            <p class="text-left">finished <span class="glyphicon glyphicon-triangle-top"></span></p>
        </div>        
        <div class="col-md-2">
            <p class="text-right">submitted <span class="glyphicon glyphicon-triangle-top"></span></p>
        </div>
        <div class="col-md-2">
            <p class="text-right">result handed in <span class="glyphicon glyphicon-triangle-top"></span></p>
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
                <small>The world might end in...<br><?= $model->time_limit ?> minutes</small>
                </h1>
            </div>            
        </div>
    </div>
