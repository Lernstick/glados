<?php

use kartik\switchinput\SwitchInput;
use kartik\select2\Select2;

/* @var $id integer */
/* @var $label string */
/* @var $hint string */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */
/* @var $members app\models\ExamSetting[] */

$keylogger_keymap = $members['keylogger_keymap'];
$keylogger_path = $members['keylogger_path'];

$id2 = $keylogger_keymap->id === null ? $id . "a" : $keylogger_keymap->id;
$id3 = $keylogger_path->id === null ? $id . "b" : $keylogger_path->id;

$js = <<< SCRIPT
$("#ExamSettings_{$id}_value").on("switchChange.bootstrapSwitch change", function(){
    if ($(this).is(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("disabled", false);
        $('#ExamSettings_{$id3}_value').attr("readonly", false);
    } else if ($(this).not(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("disabled", true);
        $('#ExamSettings_{$id3}_value').attr("readonly", true);
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
                'label' => $label
            ],
        ])->label(false)->hint($hint); ?>
    </div>
    <div class="panel-body">
        <div style="display:none;">
            <?= $form->field($keylogger_keymap, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id2}_key",
                'name' => "ExamSettings[$id2][key]",
                'data-id' => $id2,
            ])->label(false)->hint(false); ?>
            <?= $form->field($keylogger_keymap, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id2}_belongs_to",
                'name' => "ExamSettings[$id2][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
            <?= $form->field($keylogger_path, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id3}_key",
                'name' => "ExamSettings[$id3][key]",
                'data-id' => $id3,
            ])->label(false)->hint(false); ?>
            <?= $form->field($keylogger_path, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id3}_belongs_to",
                'name' => "ExamSettings[$id3][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
        </div>

        <div class="row">
            <div class="parent col-sm-6">
                <?= $form->field($keylogger_keymap, 'value')->widget(Select2::classname(), [
                    'data' => [
                        # @see https://www.localeplanet.com/icu/hu/
                        'auto' => \Yii::t('exam_setting', 'Automatic detection'),
                        'ca_FR' => \Yii::t('exam_setting', 'French (Canada)'),
                        'cs_CZ' => \Yii::t('exam_setting', 'Czech (Czechia)'),
                        'de' => \Yii::t('exam_setting', 'German'),
                        'de_CH' => \Yii::t('exam_setting', 'German (Switzerland)'),
                        'en_GB' => \Yii::t('exam_setting', 'English (United Kingdom)'),
                        'en_US_dvorak' => \Yii::t('exam_setting', 'English (United States) dvorak'),
                        'en_US_ubuntu_1204' => \Yii::t('exam_setting', 'English (United States) for Ubuntu 12.04'),
                        'es_AR' => \Yii::t('exam_setting', 'Spanish (Argentina)'),
                        'es_ES' => \Yii::t('exam_setting', 'Spanish (Spain)'),
                        'fr_CH' => \Yii::t('exam_setting', 'French (Switzerland)'),
                        'fr-dvorak-bepo' => \Yii::t('exam_setting', 'French dvorak'),
                        'fr' => \Yii::t('exam_setting', 'French'),
                        'hu' => \Yii::t('exam_setting', 'Hungarian'),
                        'it' => \Yii::t('exam_setting', 'Italian'),
                        'no' => \Yii::t('exam_setting', 'Norwegian'),
                        'pl' => \Yii::t('exam_setting', 'Polish'),
                        'pt_BR' => \Yii::t('exam_setting', 'Portuguese (Brazil)'),
                        'pt_PT' => \Yii::t('exam_setting', 'Portuguese (Portugal)'),
                        'ro' => \Yii::t('exam_setting', 'Romanian'),
                        'ru' => \Yii::t('exam_setting', 'Russian'),
                        'sk_QWERTY' => \Yii::t('exam_setting', 'Slovak (QWERTY)'),
                        'sk_QWERTZ' => \Yii::t('exam_setting', 'Slovak (QWERTZ)'),
                        'sl' => \Yii::t('exam_setting', 'Slovenian'),
                        'sv' => \Yii::t('exam_setting', 'Swedish'),
                        'tr' => \Yii::t('exam_setting', 'Turkish'),
                    ],
                    'disabled' => !$setting->value,
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'placeholder' => '',
                    ],
                    'options' => [
                        'id' => "ExamSettings_{$id2}_value",
                        'name' => "ExamSettings[$id2][value]",
                        'placeholder' => \Yii::t('exam_setting', 'Choose a keymap ...')
                    ]
                ])->label($keylogger_keymap->detail->name)
                ->hint($keylogger_keymap->detail->description); ?>
            </div>
            <div class="parent col-sm-6">
                <?= $form->field($keylogger_path, 'value', [
                   'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...to the directory') . '</div>{input}</div>{hint}{error}',
                ])->textInput([
                    'id' => "ExamSettings_{$id3}_value",
                    'name' => "ExamSettings[$id3][value]",
                    'readonly' => !$setting->value,
                ])->label($keylogger_path->detail->name)->hint($keylogger_path->detail->description); ?>
            </div>
        </div>

    </div>
</div>