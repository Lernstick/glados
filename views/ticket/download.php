<?php

use yii\helpers\Html;
use app\models\Ticket;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

if ($model->client_state == 'setup complete') {
    $this->registerJs('$("#dialog").modal("show");');
}

?>
<div class="download-view">

    <div class="row">
    <div class="col-md-6">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="row">
                <div class="col-xs-8">
                    <span>Please wait, while your system is prepared</span>
                </div>
                <div class="col-xs-4">
                    <div class="dropdown pull-right">
                        <a class="btn btn-secondary btn-xs dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="glyphicon glyphicon-option-horizontal"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <?= Html::a(
                                    '<span class="glyphicon glyphicon-backward"></span> Back to token submission',
                                    ['download', 'token' => $model->token, 'step' => 1],
                                    ['id' => 'backup-now']
                                ) ?>
                            </li>            
                            <li>
                                <?= Html::a(
                                    '<span class="glyphicon glyphicon-retweet"></span> Request download again',
                                    ['download', 'token' => $model->token, 'step' => 2],
                                    ['id' => 'backup-now']
                                ) ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-body">
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
                            s.parentNode.classList.add("active");
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
                        'style' => [
                            'width' => yii::$app->formatter->format($model->download_progress, 'percent'),
                            'min-width' => '2em',
                        ]
                    ]
                ]) . 
            '</div>';

            ?>
        </div>
          <ul class="list-group">
            <li class="list-group-item">
                <?= ActiveEventField::widget([
                    'options' => [
                        'tag' => 'i',
                        'class' => 'glyphicon glyphicon-cog ' . ($model->restore_lock == 1 ? 'gly-spin' : 'hidden'),
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
                ]); ?>&nbsp;
                <span id="info"><?= yii::$app->formatter->format($model->client_state, 'raw'); ?></span>
            </li>
          </ul>        
    </div>
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
        'content' => null,
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'download_state',
        'jsHandler' => 'function(d, s){
            $("#info").html(d);
        }'        
    ]); ?>

    <?= ActiveEventField::widget([
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'setup_complete',
        'jsHandler' => 'function(d, s){
            if (d == true) {
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
                    <p>The system setup is done. You can close this window now.</p>
                </div>

            </div>
        </div>
    </div>

</div>
