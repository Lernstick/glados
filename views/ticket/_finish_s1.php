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
/* @var $finish bool */

if (isset($finish) && $finish) {
    $this->registerJs('$("#confirm").modal("show").on("hide.bs.modal", function(e){ e.preventDefault(); });');
}

$n_more_windows = \Yii::t('client', '+{n} more windows ...');
$no_open_window = \Yii::t('client', 'No open window');
$confirm = \Yii::t('client', 'Are you sure?');

# Listen to the wxbrowser event when the script executed.
# The script output is in the argument json.
$this->registerJs(<<<END
$(document).on("wxbrowser:script", function(e, json) {
    if (json == false) {
        $("#hand-in-btn").removeClass('disabled');
        $("#hand-in-btn").data('confirm', '{$confirm}');
    } else {
        $("#hand-in-btn").data('confirm', '');
        j = JSON.parse(json)
        if (j.windows.length > 0) {
            var list = $('<ul/>');
            $.each(j.windows, function (i, value) {
                var li = $('<li/>');
                if (Number.isInteger(value)) {
                    li.append(substitute('<b>{$n_more_windows}</b>', {'n': value}));
                } else {
                    li.append($('<img/>',{class: 'live-overview-item-icon', src: 'data:image/gif;base64,' + j.icons[i]}));
                    li.append('&nbsp;' + value);
                }
                list.append(li);
            });
            $("#hand-in-btn").addClass('disabled');
        } else {
            var list = $('<span/>');
            list.append($('<span/>', {class:'glyphicon glyphicon-ok'}));
            list.append($('<span/>').html('&nbsp;{$no_open_window}'));
            $("#hand-in-btn").removeClass('disabled');
        }
        $("#open_window_list").html(list);
    }
});
END);

?>

<div class="modal modal-centered fade in" role="dialog" id="confirm" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4><?= \Yii::t('client', 'Confirm Exam Hand-in') ?></h4>
            </div>

            <div class="modal-body">
                <p><?= \Yii::t('client', "You're about to hand-in your exam.") ?></p>

                <div class="alert alert-danger" role="alert">
                    <h4><?= \Yii::t('client', 'Important!') ?></h4>
                    <p>
                        <?= \Yii::t('client', 'Please notice, that once this process is initiated, you <b>cannot continue</b> with the exam. So, please make sure you <b>saved all documents</b> and <b>closed all open windows</b> before finishing your exam.') ?>
                    </p>
                    <hr>
                    <h4><?= \Yii::t('client', 'List of open windows:') ?></h4>
                    <p id='open_window_list'></p>
                    <p>
                        <?= \Yii::t('client', 'The "hand-in" button is disabled as long as there are open windows') ?>
                    </p>
                </div>
            </div>

            <div class="modal-footer">
                <?= isset($finish) && $finish ? Html::a(\Yii::t('app', 'Cancel'), '#wxbrowser:close', [
                    'class' => 'btn btn-default',
                ]) : Html::Button(\Yii::t('app', 'Cancel'), [
                    'data-dismiss' => 'modal',
                    'class' => 'btn btn-default',
                ]); ?>
                <?= Html::a(\Yii::t('app', 'Yes, hand-in my exam now!'), Url::to([
                    'ticket/finish',
                    'token' => $model->token,
                    'step' => 2,
                ]), [
                    'id' => 'hand-in-btn',
                    'data-confirm' => $confirm,
                    'class' => 'btn btn-danger btn-danger',
                ]) ?>

            </div>
        </div>
    </div>
</div>