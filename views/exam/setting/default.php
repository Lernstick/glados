<?php

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */

?>

<?= $form->field($setting, 'value')->textInput([
    'id' => "ExamSettings_{$id}_value",
    'name' => "ExamSettings[$id][value]",
]); ?>