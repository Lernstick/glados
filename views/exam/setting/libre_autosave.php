<?php

use kartik\range\RangeInput;
use app\models\ExamSetting;

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */

$libre_autosave_interval = new ExamSetting(['key' => 'libre_autosave_interval']);
$libre_autosave_path = new ExamSetting(['key' => 'libre_autosave_path']);
$id2 = bin2hex(openssl_random_pseudo_bytes(8));
$id3 = bin2hex(openssl_random_pseudo_bytes(8));

?>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= $form->field($setting, 'value', [
            'options' => [
                'class' => ''
            ],
            'errorOptions' => ['tag' => false],
        ])->checkbox([
            'id' => "ExamSettings_{$id}_value",
            'name' => "ExamSettings[$id][value]",
        ]) ?>
    </div>
    <div class="panel-body">
        <?= $form->field($libre_autosave_path, 'key')->hiddenInput([
            'id' => "ExamSettings_{$id2}_key",
            'name' => "ExamSettings[$id2][key]",
            'data-id' => $id2,
        ])->label(false)->hint(false); ?>
        <?= $form->field($libre_autosave_path, 'value', [
            'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...to the directory') . '</div>{input}</div>{hint}{error}'
        ])->textInput([
            'id' => "ExamSettings_{$id2}_value",
            'name' => "ExamSettings[$id2][value]",
        ])->label(false); ?>
        <?= $form->field($libre_autosave_interval, 'key')->hiddenInput([
            'id' => "ExamSettings_{$id3}_key",
            'name' => "ExamSettings[$id3][key]",
            'data-id' => $id3,
        ])->label(false)->hint(false); ?>
        <?= $form->field($libre_autosave_interval, 'value', [
            'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...all {n} minutes.', [
                    'n' => '</div>{input}<span class="input-group-addon" id="basic-addon2">'
                ]) . '</span></div>{hint}{error}'
        ])->textInput([
            'id' => "ExamSettings_{$id3}_value",
            'name' => "ExamSettings[$id3][value]",
            'type' => 'number',
        ])->label(false); ?>
    </div>
</div>