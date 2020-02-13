<?php

/* @var $id integer */
/* @var $label string */
/* @var $hint string */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */
/* @var $members app\models\ExamSetting[] */

?>

<?= $form->field($setting, 'value')->textInput([
    'id' => "ExamSettings_{$id}_value",
    'name' => "ExamSettings[$id][value]",
])->label($label)->hint($hint); ?>
