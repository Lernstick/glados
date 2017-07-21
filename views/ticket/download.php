<?php

use yii\helpers\Html;
use app\models\Ticket;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

$this->title = 'Download';
$this->params['breadcrumbs'] = [
    ['label' => $model->token],
    $this->title
];

?>
<div class="download-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-md-6">
        <?php
        echo '<div class="progress">' . 
            ActiveEventField::widget([
                'content' => ActiveEventField::widget([
                    'options' => [ 'tag' => 'span' ],
                    'content' => yii::$app->formatter->format($model->download_progress, 'percent'),
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'download_progress',
                    'jsHandler' => 'function(d, s){
                        s.innerHTML = d;
                        s.parentNode.style = "width:" + d;
                    }',
                ]),
                'event' => 'ticket/' . $model->id,
                'jsonSelector' => 'download_lock',
                'jsHandler' => 'function(d, s){
                    if(d == "1"){
                        s.classList.add("active");
                    }else if(d == "0"){
                        s.classList.remove("active");
                    }
                }',
                'options' => [
                    'class' => 'progress-bar progress-bar-striped ' . ($model->download_lock == 1 ? 'active' : null),
                    'role' => '"progressbar',
                    'aria-valuenow' => '0',
                    'aria-valuemin' => '0',
                    'aria-valuemax' => '100',
                    'style' => 'width:' . yii::$app->formatter->format($model->download_progress, 'percent') . ';',
                ]
            ]) . 
        '</div>';

        ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= ActiveEventField::widget([
                'options' => [
                    'tag' => 'i',
                    'class' => 'glyphicon glyphicon-cog ' . ($model->restore_lock == 1 ? 'gly-spin' : 'hidden'),
                    'style' => 'float: left;',
                ],
                'event' => 'ticket/' . $model->id,
                'jsonSelector' => 'restore_lock',
                'jsHandler' => 'function(d, s){
                    if(d == "1"){
                        s.classList.add("gly-spin");
                        s.classList.remove("hidden");
                    }else if(d == "0"){
                        s.classList.remove("gly-spin");
                        s.classList.add("hidden");
                    }
                }',
            ]); ?>
            <div id="info"></div>
        </div>
    </div>

    <?= ActiveEventField::widget([
        'content' => null,
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'restore_state',
        'jsHandler' => 'function(d, s){
            $("#info").html(d);
        }'        
    ]); ?>

    <?= ActiveEventField::widget([
        'content' => null,
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'client_state',
        'jsHandler' => 'function(d, s){
            $("#info").html(d);
        }'        
    ]); ?>

    <?= ActiveEventField::widget([
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'setup_complete',
        'jsHandler' => 'function(d, s){
            if (d == true && YII_DEBUG) {
                $("#dialog").modal("show");
            }
        }'        
    ]); ?>

    <div id="dialog" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4>Continue</h4>
                </div>

                <div class="modal-body">
                    <p>The system setup is done. You can close the window.</p>
                </div>

            </div>
        </div>
    </div>

</div>
