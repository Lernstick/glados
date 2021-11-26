<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

$this->title = \Yii::t('client', 'Exam Status');

// froces wxbrowser to resize the window
$js = <<< 'SCRIPT'
window.location.href = '#wxbrowser:resize:800x600'
SCRIPT;
$this->registerJs($js);

if ($model->last_backup == 1) {
    $this->registerJs('$("#success").modal("show").on("hide.bs.modal", function(e){
      e.preventDefault();
    });');
} else {
    $this->registerJs('$("#progress").modal("show").on("hide.bs.modal", function(e){
      e.preventDefault();
    });');
}


?>

<?= ActiveEventField::widget([
    'content' => null,
    'event' => 'ticket/' . $model->id,
    'jsonSelector' => 'last_backup',
    'jsHandler' => 'function(d, s){
        if (d == "1") {
            $("#progress").remove();
            $("#success").modal("show");
        }
    }',
]); ?>

<?= ActiveEventField::widget([
    'content' => null,
    'event' => 'ticket/' . $model->id,
    'jsonSelector' => 'backup_state',
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

<div class="finish-s2-view">

    <div id="progress" class="modal modal-centered" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h4><?= \Yii::t('client', 'Hand-in') ?></h4>
                </div>

                <div class="modal-body clearfix">
                    <p><?= \Yii::t('app', 'You exam is requested for hand-in. Please wait while your data is transferred.'); ?></p>
                    <?= ActiveEventField::widget([
                        'options' => [
                            'tag' => 'i',
                            'class' => 'glyphicon glyphicon-cog ' . ($model->backup_lock == 1 ? 'gly-spin' : 'hidden'),
                        ],
                        'event' => 'ticket/' . $model->id,
                        'jsonSelector' => 'backup_lock',
                        'jsHandler' => 'function(d, s){
                            if (d == "1") {
                                s.classList.add("gly-spin");
                                s.classList.remove("hidden");
                            } else if(d == "0") {
                                s.classList.remove("gly-spin");
                                s.classList.add("hidden");
                            }
                        }',
                    ]); ?>&nbsp;
                    <span id="info"><?= yii::$app->formatter->format($model->client_state, 'raw'); ?></span>
                </div>


                <div class="modal-footer">
                    <div class="pull-right">
                        <?= Html::a(\Yii::t('app', 'Close'), '#wxbrowser:close', ['class' => 'btn btn-default']); ?>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <div id="success" class="modal modal-centered fade" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
                    <h4><?= \Yii::t('client', 'Continue') ?></h4>
                </div>

                <div class="modal-body clearfix">
                    <p><?= \Yii::t('client', 'You exam was handed-in successfully ({date}). You can close this window now.', [
                            'date' => yii::$app->formatter->format($model->end, 'timeago'),
                        ]) ?></p>
                    <div class="pull-right">
                        <?= Html::a(\Yii::t('app', 'Close'), '#wxbrowser:close', ['class' => 'btn btn-default']); ?>
                        <?= Html::a(\Yii::t('app', 'Shutdown'), '#wxbrowser:shutdown', ['class' => 'btn btn-danger']); ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>