<?php

use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\Pjax;

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */

?>

<div class="row item item<?= $id ?>">
    <div class="col-md-4">
        <?= $form->field($setting, 'key')->dropdownList($id == "__id__" ? [] : [
            $setting->key => $setting->detail->name,
        ], [
            'id' => "ExamSettings_{$id}_key",
            'name' => "ExamSettings[$id][key]",
            'data-id' => $id,
        ])->hint($id == "__id__" ? false : $setting->detail->description); ?>
    </div>

    <?php Pjax::begin([
        'id' => 'item' . $id,
        'options' => ['class' => 'col-md-7'],
    ]); ?>


    <?php

    echo $this->render('setting/value', [
        'id' => $id,
        'form' => $form,
        'setting' => $setting,
    ]);

    ?>


    <?php Pjax::end(); ?>

    <div class="col-md-1">
        <?= Html::a('Remove', 'javascript:void(0);', [
          'class' => 'exam-remove-setting-button btn btn-default btn-xs',
        ]) ?>
    </div>
</div>