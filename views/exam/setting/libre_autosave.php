<?php

use app\models\ExamSetting;

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */
/* @var $members app\models\ExamSetting[] */

$libre_autosave_interval = new ExamSetting(['key' => 'libre_autosave_interval']);
$libre_autosave_path = new ExamSetting(['key' => 'libre_autosave_path']);

foreach($members as $s) {
    if ($s->key == 'libre_autosave_interval') {
        $libre_autosave_interval = $s;
    } else if ($s->key == 'libre_autosave_path') {
        $libre_autosave_path = $s;
    }
}

$libre_autosave_interval->loadDefaultValue();
$libre_autosave_path->loadDefaultValue();

$id2 = $libre_autosave_path->id === null ? $id . "b" : $libre_autosave_path->id;
$id3 = $libre_autosave_interval->id === null ? $id . "a" : $libre_autosave_interval->id;

$js = <<< SCRIPT
$("#ExamSettings_{$id}_value").click(function(){
    if ($(this).is(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("disabled", false);
        $('#ExamSettings_{$id3}_value').attr("disabled", false);
    } else if ($(this).not(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("disabled", true);
        $('#ExamSettings_{$id3}_value').attr("disabled", true);
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
            //'errorOptions' => ['tag' => false],
        ])->checkbox([
            'id' => "ExamSettings_{$id}_value",
            'name' => "ExamSettings[$id][value]",
            'label' => $setting->detail->name,
        ])->label(false) ?>
    </div>
    <div class="panel-body">
        <div style="display:none;">
            <?= $form->field($libre_autosave_path, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id2}_key",
                'name' => "ExamSettings[$id2][key]",
                'data-id' => $id2,
            ])->label(false)->hint(false); ?>
            <?= $form->field($libre_autosave_path, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id2}_belongs_to",
                'name' => "ExamSettings[$id2][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
            <?= $form->field($libre_autosave_interval, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id3}_key",
                'name' => "ExamSettings[$id3][key]",
                'data-id' => $id3,
            ])->label(false)->hint(false); ?>
            <?= $form->field($libre_autosave_interval, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id3}_belongs_to",
                'name' => "ExamSettings[$id3][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
        </div>
        <?= $form->field($libre_autosave_path, 'value', [
            'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...to the directory') . '</div>{input}</div>{hint}{error}'
        ])->textInput([
            'id' => "ExamSettings_{$id2}_value",
            'name' => "ExamSettings[$id2][value]",
            'disabled' => !$setting->value,
        ])->label(false); ?>
        <?= $form->field($libre_autosave_interval, 'value', [
            'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...all {n} minutes.', [
                    'n' => '</div>{input}<span class="input-group-addon" id="basic-addon2">'
                ]) . '</span></div>{hint}{error}'
        ])->textInput([
            'id' => "ExamSettings_{$id3}_value",
            'name' => "ExamSettings[$id3][value]",
            'type' => 'number',
            'disabled' => !$setting->value,
        ])->label(false); ?>
    </div>
</div>