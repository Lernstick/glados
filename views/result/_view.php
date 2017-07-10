<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */


if (file_exists($model->result)) {

    $active_tabs = <<<JS
    document.getElementById('progressbar').style = "width:100%";
JS;
    $this->registerJs($active_tabs);

    echo '<div class="jumbotron">' . 
        '<h1>Hello ' . ($model->test_taker ? $model->test_taker : $model->token) . '</h1>' . 
        '<p>Your exam result is handed in, check it out!</p>' . 
        '<p>' . 
        Html::a(
            '<span class="glyphicon glyphicon-save-file"></span> Download my result',
            ['result/download', 'token' => $model->token],
            ['class' => 'btn btn-primary btn-lg', 'role' => 'button']
        ) . 
        '</div>';
} else {
    $active_tabs = <<<JS
    document.getElementById('progressbar').style = "width:82.5%";
JS;
    $this->registerJs($active_tabs);   
}

?>

<div class="progress">
  <div id="progressbar" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
  </div>
</div>

    <div class="row">
        <div class="col-md-8">
            <p class="text-left"><span class="glyphicon glyphicon-menu-up"></span> started</p>
        </div>
        <div class="col-md-2">
            <p class="text-right">finished <span class="glyphicon glyphicon-menu-up"></span></p>
        </div>
        <div class="col-md-2">
            <p class="text-right">result handed in <span class="glyphicon glyphicon-menu-up"></span></p>
        </div>
    </div>

<hr>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'token',
        'exam.name',
        'start:timeago',
        'end:timeago',
        'duration:duration',
        'test_taker',
    ],
]); ?>
