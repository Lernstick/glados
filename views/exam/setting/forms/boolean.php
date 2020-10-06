<?php

use kartik\switchinput\SwitchInput;

/* @var $id integer */
/* @var $label string */
/* @var $hint string */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */
/* @var $members app\models\ExamSetting[] */

?>

<?= $form->field($setting, 'value', [
    'options' => [
        'class' => ''
    ],
])->widget(SwitchInput::classname(), [
    'pluginOptions' => [
        'size' => 'mini',
        'onText' => \Yii::t('app', 'ON'),
        'offText' => \Yii::t('app', 'OFF'),
    ],
    'options' => [
        'id' => "ExamSettings_{$id}_value",
        'name' => "ExamSettings[$id][value]",
        'label' => $label,
        'labelOptions' => [
            'class' => 'control-label',
        ]
    ],
])->label(false)->hint($hint); ?>