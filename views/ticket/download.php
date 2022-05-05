<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Ticket;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

$this->title = \Yii::t('client', 'Exam Client');

if ($model->client_state == 'setup complete') {
    $this->registerJs('$("#dialog").modal("show");');
}

?>
<div class="download-view">

    <div class="row">
    <div class="col-md-12 col-xs-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-8 col-xs-8">
                    <span><?= \Yii::t('client', 'Please wait, while your system is prepared') ?></span>
                </div>
                <div class="col-md-4 col-xs-4">
                    <div class="dropdown pull-right">
                        <a class="btn btn-secondary btn-xs dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="glyphicon glyphicon-option-horizontal"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <?= Html::a(
                                    '<span class="glyphicon glyphicon-backward"></span> ' . \Yii::t('client', 'Back to token submission'),
                                    ['download', 'token' => $model->token, 'step' => 1]
                                ) ?>
                            </li>            
                            <li>
                                <?= Html::a(
                                    '<span class="glyphicon glyphicon-retweet"></span> ' . \Yii::t('client', 'Request download again'),
                                    ['download', 'token' => $model->token, 'step' => 2]
                                ) ?>
                            </li>
                            <li>
                                <?= Html::a(
                                    '<span class="glyphicon glyphicon-question-sign"></span> ' . \Yii::t('client', 'Help'),
                                    ['howto/view', 'id' => 'token-request-help.md', 'mode' => 'inline'],
                                    ['onclick' => 'window.open("' . Url::to(['howto/view', 'id' => 'token-request-help.md', 'mode' => 'inline']) . '", "Help", "titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=800"); return false;']
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
                            'width' => $model->download_progress*100 . '%',
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
                <span id="info"><?= yii::$app->formatter->asLInks($model->client_state, ['remove' => true]); ?></span>
            </li>
          </ul>
    </div>
    </div>
    </div>

    <?= ActiveEventField::widget([
        'content' => null,
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'restore_state',
        'jsFormatter' => ['links', ['remove' => true]],
        'jsHandler' => 'function(d, s){
            $("#info").html(d);
        }'
    ]); ?>

    <?= ActiveEventField::widget([
        'content' => null,
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'client_state',
        'jsFormatter' => ['links', ['remove' => true]],
        'jsHandler' => 'function(d, s){
            $("#info").html(d);
        }'
    ]); ?>

    <?= ActiveEventField::widget([
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'setup_complete',
        'jsHandler' => 'function(d, s){
            if (d == true) {
                $("#success").modal("show");
            }
        }'
    ]); ?>

    <?= ActiveEventField::widget([
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'setup_failed',
        'jsHandler' => 'function(d, s){
            if (d == true) {
                $("#fail").modal("show");
            }
        }'
    ]); ?>

    <div id="success" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4><?= \Yii::t('client', 'Continue') ?></h4>
                </div>

                <div class="modal-body">
                    <p><?= \Yii::t('client', 'The system setup is done. You can close this window now.') ?></p>
                </div>

            </div>
        </div>
    </div>

    <div id="fail" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4><?= \Yii::t('client', 'Abort') ?></h4>
                </div>

                <div class="modal-body">
                    <p><?= \Yii::t('client', 'The system setup has failed.') ?></p>
                </div>
                <div class="modal-footer">
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-retweet"></span> ' . \Yii::t('client', 'Request download again'),
                        ['download', 'token' => $model->token, 'step' => 2],
                        ['class' => 'btn btn-danger']
                    ) ?>
                </div>

            </div>
        </div>
    </div>

</div>