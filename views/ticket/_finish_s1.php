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
    $this->registerJs('$("#confirm").modal("show").on("hide.bs.modal", function(e){
      e.preventDefault();
    });');
}

?>

<div class="modal modal-centered fade in" role="dialog" id="confirm" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                <h4><?= \Yii::t('client', 'Confirm Exam Hand-in') ?></h4>
            </div>

            <div class="modal-body">
                <p>You're about to hand-in your exam.</p>

                <div class="alert alert-danger" role="alert">
                    <h4><?= \Yii::t('client', 'Important!') ?></h4>
                    <p>
                        <?= \Yii::t('client', 'Please notice, that once this process is initiated, you <b>cannot continue</b> with the exam. So, please make sure you <b>saved all documents</b> and <b>closed all open windows</b> before finishing your exam.') ?>
                    </p>
                </div>
            </div>

            <div class="modal-footer">
                <?= isset($finish) && $finish ? Html::a(\Yii::t('app', 'Cancel'), '#wxbrowser:close', [
                    'class' => 'btn btn-default'
                ]) : Html::Button(\Yii::t('app', 'Cancel'), [
                    'data-dismiss' => 'modal',
                    'class' => 'btn btn-default'
                ]); ?>
                <?= Html::a(\Yii::t('app', 'Yes, hand-in my exam'), Url::to([
                    'ticket/finish',
                    'token' => $model->token,
                    'step' => 2,
                ]), [
                    'class' => 'btn btn-danger btn-danger',
                ]) ?>

            </div>
        </div>
    </div>
</div>