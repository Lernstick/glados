<?php

use kartik\range\RangeInput;

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */

?>

<?= $form->field($setting, 'value')->widget(RangeInput::classname(), [
    'options' => [
		'id' => "ExamSettings_{$id}_value",
		'name' => "ExamSettings[$id][value]",
    	'placeholder' => \Yii::t('exams', 'Select range ...'),
    ],
    'html5Options' => ['min' => 0, 'max' => 100, 'step' => 1],
    'html5Container' => ['style' => 'width:80%'],
    'addon' => ['append' => ['content' => '%']]
]) ?>