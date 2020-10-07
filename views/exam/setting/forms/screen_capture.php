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
$screen_capture_chunk = $members['screen_capture_chunk'];
$screen_capture_bitrate = $members['screen_capture_bitrate'];
$screen_capture_path = $members['screen_capture_path'];
$screen_capture_overflow_threshold = $members['screen_capture_overflow_threshold'];

$id2 = $screen_capture_command->id === null ? $id . "a" : $screen_capture_command->id;
$id3 = $screen_capture_fps->id === null ? $id . "b" : $screen_capture_fps->id;
$id5 = $screen_capture_chunk->id === null ? $id . "d" : $screen_capture_chunk->id;
$id6 = $screen_capture_bitrate->id === null ? $id . "e" : $screen_capture_bitrate->id;
$id7 = $screen_capture_path->id === null ? $id . "f" : $screen_capture_path->id;
$id8 = $screen_capture_overflow_threshold->id === null ? $id . "g" : $screen_capture_overflow_threshold->id;

$js = <<< SCRIPT
$("#ExamSettings_{$id}_value").on("switchChange.bootstrapSwitch change", function(){
    if ($(this).is(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("readonly", false);
        $('#ExamSettings_{$id3}_value').attr("readonly", false);
        $('#ExamSettings_{$id5}_value').attr("readonly", false);
        $('#ExamSettings_{$id6}_value').attr("readonly", false);
        $('#ExamSettings_{$id7}_value').attr("readonly", false);
        $('#ExamSettings_{$id8}_value').attr("readonly", false);
    } else if ($(this).not(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("readonly", true);
        $('#ExamSettings_{$id3}_value').attr("readonly", true);
        $('#ExamSettings_{$id5}_value').attr("readonly", true);
        $('#ExamSettings_{$id6}_value').attr("readonly", true);
        $('#ExamSettings_{$id7}_value').attr("readonly", true);
        $('#ExamSettings_{$id8}_value').attr("readonly", true);
    }
});

mode = $("#mode");
mode_change = function(){
    console.log("mode_change");
    var selected = $(this).children("option:selected").val();
    var command = $('#ExamSettings_{$id2}_value').closest("div.parent");
    var fps = $('#ExamSettings_{$id3}_value').closest("div.parent");
    var chunk = $('#ExamSettings_{$id5}_value').closest("div.parent");
    var bitrate = $('#ExamSettings_{$id6}_value').closest("div.parent");
    var path = $('#ExamSettings_{$id7}_value').closest("div.parent");

    if (selected == null) {
        selected = "options";
    }

    if (selected == "options") {
        command.hide();
        path.hide();
        fps.show();
        chunk.show();
        bitrate.show();
    } else if (selected == "command") {
        command.show();
        path.show();
        fps.hide();
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
            <?= $form->field($screen_capture_path, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id7}_key",
                'name' => "ExamSettings[$id7][key]",
                'data-id' => $id7,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_path, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id7}_belongs_to",
                'name' => "ExamSettings[$id7][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_overflow_threshold, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id8}_key",
                'name' => "ExamSettings[$id8][key]",
                'data-id' => $id8,
            ])->label(false)->hint(false); ?>
            <?= $form->field($screen_capture_overflow_threshold, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id8}_belongs_to",
                'name' => "ExamSettings[$id8][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
        </div>

        <div class="row">
            <div class="col-sm-6 form-inline form-group">
                <?= Html::label(\Yii::t('exam_setting', 'Mode')); ?>
                <?= Html::dropdownList('mode', 'options', [
                    'options' => \Yii::t('exam_setting', 'Use options'),
                    'command' => \Yii::t('exam_setting', 'Provide a command'),
                ], [
                    'id' => 'mode',
                    'class' => 'form-control',
                ]) ?>
            </div>
        </div>

        <div class="parent">
            <?= $form->field($screen_capture_command, 'value', [
            ])->textarea([
                'id' => "ExamSettings_{$id2}_value",
                'name' => "ExamSettings[$id2][value]",
                'rows' => '5',
                'readonly' => !$setting->value,
            ])->label($screen_capture_command->detail->name)->hint($screen_capture_command->detail->description); 
            ?>
        </div>

        <div class="row">
            <div class="parent col-sm-6">
                <?= $form->field($screen_capture_fps, 'value', [
                    'template' => '{label}<div class="input-group">' . \Yii::t('exams', '{n} frames per seond.', [
                            'n' => '{input}<span class="input-group-addon" id="basic-addon2">'
                        ]) . '</span></div>{hint}{error}'
                ])->textInput([
                    'id' => "ExamSettings_{$id3}_value",
                    'name' => "ExamSettings[$id3][value]",
                    //'type' => 'number',
                    'readonly' => !$setting->value,
                ])->label($screen_capture_fps->detail->name)->hint($screen_capture_fps->detail->description); ?>
            </div>
            <div class="parent col-sm-6">
                <?= $form->field($screen_capture_chunk, 'value', [
                    'template' => '{label}<div class="input-group">' . \Yii::t('exams', '{n} seconds.', [
                            'n' => '{input}<span class="input-group-addon" id="basic-addon2">'
                        ]) . '</span></div>{hint}{error}'
                ])->textInput([
                    'id' => "ExamSettings_{$id5}_value",
                    'name' => "ExamSettings[$id5][value]",
                    //'type' => 'number',
                    'readonly' => !$setting->value,
                ])->label($screen_capture_chunk->detail->name)->hint($screen_capture_chunk->detail->description); ?>
            </div>
        </div>

        <div class="row">
            <div class="parent col-sm-6">
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
                        'readonly' => !$setting->value,
                    ],
                ])->label($screen_capture_bitrate->detail->name)->hint($screen_capture_bitrate->detail->description); ?>
            </div>
        </div>

        <div class="parent">
            <?= $form->field($screen_capture_path, 'value', [
                'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...to the directory') . '</div>{input}</div>{hint}{error}'
            ])->textInput([
                'id' => "ExamSettings_{$id7}_value",
                'name' => "ExamSettings[$id7][value]",
                'type' => 'text',
                'readonly' => !$setting->value,
            ])->label($screen_capture_path->detail->name)->hint($screen_capture_path->detail->description); ?>
        </div>

        <div class="parent">
            <?= $form->field($screen_capture_overflow_threshold, 'value', [
                'template' => '{label}<div class="input-group"><span class="input-group-addon">' . \Yii::t('exams', 'Remove capture files when they exceed {threshold} of total disk space.', [
                            'threshold' => '</span>{input}<span class="input-group-addon">'
                        ]) . '</span></div>{hint}{error}'
            ])->widget(\yii\widgets\MaskedInput::className(), [
                'type' => 'text',
                //'mask' => ['9{1,4}MB', '9{1,2}\%'],
                'mask' => ['9{1,4}m', '9{1,2}\%'],
                'options' => [
                    'id' => "ExamSettings_{$id8}_value",
                    'name' => "ExamSettings[$id8][value]",
                    'readonly' => !$setting->value,
                ],
            ])->label($screen_capture_overflow_threshold->detail->name)->hint($screen_capture_overflow_threshold->detail->description); ?>
        </div>

    </div>
</div>