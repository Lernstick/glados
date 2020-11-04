<?php

/* @var $id integer */
/* @var $belongs_to integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */

$name = $setting->detail === null ? $setting->key : $setting->detail->name;
$description = $setting->detail === null ? $setting->key : $setting->detail->description;

?>

<?= $form->field($setting, 'key')->dropdownList($id == "__id__" ? [] : [
    $setting->key => $name,
], [
    'id' => "ExamSettings_{$id}_key",
    'name' => "ExamSettings[$id][key]",
    'data-id' => $id,
])->hint($id == "__id__" ? false : $description); ?>