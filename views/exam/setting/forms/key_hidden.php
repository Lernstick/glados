<?php

/* @var $id integer */
/* @var $belongs_to integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */

?>

<div style="display:none;">
    <?= $form->field($setting, 'key')->hiddenInput([
        'id' => "ExamSettings_{$id}_key",
        'name' => "ExamSettings[$id][key]",
        'data-id' => $id,
    ])->label(false)->hint(false); ?>
    <?= $form->field($setting, 'key')->hiddenInput([
        'id' => "ExamSettings_{$id}_belongs_to",
        'name' => "ExamSettings[$id][belongs_to]",
        'value' => $belongs_to,
    ])->label(false)->hint(false); ?>
</div>