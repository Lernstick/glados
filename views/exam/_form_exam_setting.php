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
    <div class="col-md-11">
        <div class="col-md-12 key <?= $id != "__id__" ? 'hidden' : ''?>">
            <?= $this->render('setting/forms/key', [
                'id' => $id,
                'form' => $form,
                'setting' => $setting,
            ]); ?>
        </div>

        <?php Pjax::begin([
            'id' => 'item' . $id,
            'options' => ['class' => 'col-md-12'],
        ]); ?>

            <?= $id == "__id__" ? '' : $this->render('setting/forms/value', [
                'id' => $id,
                'form' => $form,
                'setting' => $setting,
            ]); ?>

        <?php Pjax::end(); ?>
    </div>

    <div class="col-md-1">
        <?= Html::a('<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>', 'javascript:void(0);', [
          'class' => 'exam-remove-setting-button btn btn-danger btn-xs',
        ]) ?>
    </div>


</div>