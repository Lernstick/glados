<?php

use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\Pjax;

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */

?>

<div class="row item item<?= $id ?>">
    <hr>
    <div class="col-md-4">
        <?= $this->render('setting/forms/key', [
            'id' => $id,
            'form' => $form,
            'setting' => $setting,
        ]); ?>
    </div>

    <?php Pjax::begin([
        'id' => 'item' . $id,
        'options' => ['class' => 'col-md-7'],
    ]); ?>

        <?= $this->render('setting/forms/value', [
            'id' => $id,
            'form' => $form,
            'setting' => $setting,
        ]); ?>

    <?php Pjax::end(); ?>

    <div class="col-md-1">
        <?= Html::a('Remove', 'javascript:void(0);', [
          'class' => 'exam-remove-setting-button btn btn-default btn-xs',
        ]) ?>
    </div>
</div>