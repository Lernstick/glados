<?php

use app\models\ExamSetting;
use kartik\switchinput\SwitchInput;

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */
/* @var $members app\models\ExamSetting[] */

$screenshots_interval = new ExamSetting(['key' => 'screenshots_interval']);

foreach($members as $s) {
    if ($s->key == 'screenshots_interval') {
        $screenshots_interval = $s;
    }
}

$screenshots_interval->loadDefaultValue();

$id2 = $screenshots_interval->id === null ? $id . "a" : $screenshots_interval->id;

$js = <<< SCRIPT
$("#ExamSettings_{$id}_value").on("switchChange.bootstrapSwitch change", function(){
    if ($(this).is(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("disabled", false);
    } else if ($(this).not(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("disabled", true);
    }
});
SCRIPT;
$this->registerJs($js);

?>

<div class="panel panel-default">
    <div class="panel-heading">
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
                'label' => $setting->detail->name
            ],
        ])->label(false); ?>
    </div>
    <div class="panel-body">
        <div style="display:none;">
            <?= $form->field($screenshots_interval, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id2}_key",
                'name' => "ExamSettings[$id2][key]",
                'data-id' => $id2,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screenshots_interval, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id2}_belongs_to",
                'name' => "ExamSettings[$id2][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
        </div>
        <?= $form->field($screenshots_interval, 'value', [
            'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', 'with Interval of') . '</div>{input}<span class="input-group-addon" id="basic-addon2">' . \Yii::t('exams', 'minutes') . '</span></div>{hint}{error}'
        ])->textInput([
            'type' => 'number',
            'id' => "ExamSettings_{$id2}_value",
            'name' => "ExamSettings[$id2][value]",
            'disabled' => !$setting->value
        ])->label(false); ?>
    </div>
</div>