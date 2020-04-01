<?php

use kartik\switchinput\SwitchInput;
use kartik\range\RangeInput;
use yii\helpers\Html;

/* @var $id integer */
/* @var $label string */
/* @var $hint string */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */
/* @var $members app\models\ExamSetting[] */

$screen_capture_command = $members['screen_capture_command'];
$screen_capture_fps = $members['screen_capture_fps'];
$screen_capture_quality = $members['screen_capture_quality'];
$screen_capture_chunk = $members['screen_capture_chunk'];
$screen_capture_bitrate = $members['screen_capture_bitrate'];

$id2 = $screen_capture_command->id === null ? $id . "a" : $screen_capture_command->id;
$id3 = $screen_capture_fps->id === null ? $id . "b" : $screen_capture_fps->id;
$id4 = $screen_capture_quality->id === null ? $id . "c" : $screen_capture_quality->id;
$id5 = $screen_capture_chunk->id === null ? $id . "d" : $screen_capture_chunk->id;
$id6 = $screen_capture_bitrate->id === null ? $id . "e" : $screen_capture_bitrate->id;

$screen_capture_quality->value *= 100;

$js = <<< SCRIPT
$("#ExamSettings_{$id}_value").on("switchChange.bootstrapSwitch change", function(){
    if ($(this).is(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("disabled", false);
        $('#ExamSettings_{$id3}_value').attr("disabled", false);
        $('#ExamSettings_{$id4}_value').attr("disabled", false);
        $('#ExamSettings_{$id5}_value').attr("disabled", false);
        $('#ExamSettings_{$id6}_value').attr("disabled", false);
    } else if ($(this).not(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("disabled", true);
        $('#ExamSettings_{$id3}_value').attr("disabled", true);
        $('#ExamSettings_{$id4}_value').attr("disabled", true);
        $('#ExamSettings_{$id5}_value').attr("disabled", true);
        $('#ExamSettings_{$id6}_value').attr("disabled", true);
    }
});

mode = $("#mode");
mode_change = function(){
    console.log("mode_change");
    var selected = $(this).children("option:selected").val();
    var command = $('#ExamSettings_{$id2}_value').closest("div.parent");
    var fps = $('#ExamSettings_{$id3}_value').closest("div.parent");
    var quality = $('#ExamSettings_{$id4}_value').closest("div.parent");
    var chunk = $('#ExamSettings_{$id5}_value').closest("div.parent");
    var bitrate = $('#ExamSettings_{$id6}_value').closest("div.parent");

    if (selected == null) {
        selected = "options";
    }

    if (selected == "options") {
        command.hide();
        fps.show();
        quality.show();
        chunk.show();
        bitrate.show();
    } else if (selected == "command") {
        command.show();
        fps.hide();
        quality.hide();
        chunk.hide();
        bitrate.hide();
    }
};

mode_change();
mode.change(mode_change);

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
                'label' => $label
            ],
        ])->label(false)->hint($hint); ?>
    </div>
    <div class="panel-body">
        <div style="display:none;">
            <?= $form->field($screen_capture_command, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id2}_key",
                'name' => "ExamSettings[$id2][key]",
                'data-id' => $id2,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_command, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id2}_belongs_to",
                'name' => "ExamSettings[$id2][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_quality, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id4}_key",
                'name' => "ExamSettings[$id4][key]",
                'data-id' => $id4,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_quality, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id4}_belongs_to",
                'name' => "ExamSettings[$id4][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_fps, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id3}_key",
                'name' => "ExamSettings[$id3][key]",
                'data-id' => $id3,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_fps, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id3}_belongs_to",
                'name' => "ExamSettings[$id3][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_chunk, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id5}_key",
                'name' => "ExamSettings[$id5][key]",
                'data-id' => $id5,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_chunk, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id5}_belongs_to",
                'name' => "ExamSettings[$id5][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_bitrate, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id6}_key",
                'name' => "ExamSettings[$id6][key]",
                'data-id' => $id6,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_bitrate, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id6}_belongs_to",
                'name' => "ExamSettings[$id6][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
        </div>

        <?= Html::label(\Yii::t('exam_setting', 'Mode')); ?>
        <?= Html::dropdownList('mode', 'options', [
            'options' => 'Use options',
            'command' => 'Provide a command',
        ], [
            'id' => 'mode'
        ]) ?>


        <div class="parent">
            <?= $form->field($screen_capture_command, 'value', [
            ])->textarea([
                'id' => "ExamSettings_{$id2}_value",
                'name' => "ExamSettings[$id2][value]",
                'rows' => '5',
                'disabled' => !$setting->value,
            ])->label($screen_capture_command->detail->name)->hint($screen_capture_command->detail->description); 
            ?>
        </div>

        <div class="parent">
            <?= $form->field($screen_capture_quality, 'value', [
                'inputOptions' => [
                    'style' => ['min-width' => '50px'],
                ],
            ])->widget(RangeInput::classname(), [
                'options' => [
                    'id' => "ExamSettings_{$id4}_value",
                    'name' => "ExamSettings[$id4][value]",
                    'disabled' => !$setting->value,
                    'placeholder' => \Yii::t('exams', 'Select range ...'),
                ],
                'html5Options' => ['min' => 0, 'max' => 100, 'step' => 1],
                'html5Container' => ['style' => 'width:70%'],
                'addon' => [
                    'append' => ['content' => '%'],
                ],
            ])->label($screen_capture_quality->detail->name)->hint($setting->detail->description); ?>
        </div>

        <div class="parent">
            <?= $form->field($screen_capture_fps, 'value', [
                'template' => '{label}<div class="input-group">' . \Yii::t('exams', '{n} frames per seond.', [
                        'n' => '{input}<span class="input-group-addon" id="basic-addon2">'
                    ]) . '</span></div>{hint}{error}'
            ])->textInput([
                'id' => "ExamSettings_{$id3}_value",
                'name' => "ExamSettings[$id3][value]",
                'type' => 'number',
                'disabled' => !$setting->value,
            ])->label($screen_capture_fps->detail->name)->hint($screen_capture_fps->detail->description); ?>
        </div>

        <div class="parent">
            <?= $form->field($screen_capture_chunk, 'value', [
                'template' => '{label}<div class="input-group">' . \Yii::t('exams', '{n} seconds.', [
                        'n' => '{input}<span class="input-group-addon" id="basic-addon2">'
                    ]) . '</span></div>{hint}{error}'
            ])->textInput([
                'id' => "ExamSettings_{$id5}_value",
                'name' => "ExamSettings[$id5][value]",
                'type' => 'number',
                'disabled' => !$setting->value,
            ])->label($screen_capture_chunk->detail->name)->hint($screen_capture_chunk->detail->description); ?>
        </div>

        <div class="parent">
            <?= $form->field($screen_capture_bitrate, 'value', [
                'template' => '{label}<div class="input-group">' . \Yii::t('exams', '{n} bits per second.', [
                        'n' => '{input}<span class="input-group-addon" id="basic-addon2">'
                    ]) . '</span></div>{hint}{error}'
            ])->widget(\yii\widgets\MaskedInput::className(), [
                'type' => 'text',
                'mask' => ['9{1,4}(\k|\K|\m|\M){1}'],
                'options' => [
                    'id' => "ExamSettings_{$id6}_value",
                    'name' => "ExamSettings[$id6][value]",
                    'disabled' => !$setting->value,
                ],
            ])->label($screen_capture_bitrate->detail->name)->hint($screen_capture_bitrate->detail->description); ?>
        </div>
    </div>
</div>