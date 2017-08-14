<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
//use kartik\file\FileInput;
use yii\web\JsExpression;
use limion\jqueryfileupload\JQueryFileUpload;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */
/* @var $form yii\widgets\ActiveForm */

if($model->file && Yii::$app->file->set($model->file)->exists) {
    $js = new JsExpression("
        var files = [
            {
                'name':'" . basename($model->file) . "',
                'size':" . filesize($model->file) . ",
                'deleteUrl':'index.php?r=exam/delete&id=" . $model->id . "&mode=squashfs&file=" . basename($model->file) . "',
                'deleteType':'POST'
            }
        ];
    ");
    $this->registerJs($js);
}

$js = <<< 'SCRIPT'
/* To initialize BS3 popovers set this below */
$(function () { 
    $("[data-toggle='popover']").popover(); 
});

$('.hint-block').each(function () {
    var $hint = $(this);

    $hint.parent().find('label').after('&nbsp<a tabindex="0" role="button" class="hint glyphicon glyphicon-question-sign"></a>');

    $hint.parent().find('a.hint').popover({
        html: true,
        trigger: 'focus',
        placement: 'right',
        //title:  $hint.parent().find('label').html(),
        title:  'Description',
        toggle: 'popover',
        container: 'body',
        content: $hint.html()
    });

    $hint.remove()
});
SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);

?>

<div class="exam-form">

    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'subject')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'time_limit', [
                'template' => '{label}<div class="input-group">{input}<span class="input-group-addon" id="basic-addon2">minutes</span></div>{hint}{error}'
            ])->textInput(['type' => 'number']); ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'backup_path')->textInput(['maxlength' => true]) ?>
        </div>        
    </div>
    <hr />
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'grp_netdev')->checkbox() ?>
            <?= $form->field($model, 'allow_sudo')->checkbox() ?>
            <?= $form->field($model, 'allow_mount')->checkbox() ?>
            <?= $form->field($model, 'firewall_off')->checkbox() ?>
            <?= $form->field($model, 'screenshots')->checkbox() ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'url_whitelist')->textarea([
                'rows' => '6',
            ]) ?>
        </div>
    </div>
    <hr />

    <div class="row">
        <div class="col-md-12">
            <?php
            if(!$model->isNewRecord) {

                echo Html::label('File');
                echo JQueryFileUpload::widget([
                    'model' => $model,
                    'name' => 'file',
                    'url' => ['update', 'id' => $model->id, 'mode' => 'upload'],
                    'appearance' => 'ui', // available values: 'ui','plus' or 'basic'
                    'formId' => $form->id,
                    'options' => [
                        'multiple' => false
                    ],
                    'clientOptions' => [
                        'maxFileSize' => 4000000000,
                        'dataType' => 'json',
                        'acceptFileTypes' => new yii\web\JsExpression('/(\.|\/)(squashfs)$/i'),
                        'maxNumberOfFiles' => 1,
                        'autoUpload' => false
                    ],
                ]);
                echo Html::activeHint($model, 'file', ['class' => 'hint-block']);
            }
            ?>

            <?php
            if($model->file && Yii::$app->file->set($model->file)->exists) {
                $js = new JsExpression('var fupload = jQuery("#w0").fileupload({
                    "maxFileSize":4000000000,
                    "dataType":"json",
                    "acceptFileTypes":/(\.|\/)(squashfs)$/i,
                    "maxNumberOfFiles":1,
                    "autoUpload":false,
                    "url":' . json_encode(Url::to(['update', 'id' => $model->id, 'mode' => 'upload']), JSON_HEX_AMP) . ',
                    progressServerRate: 0.5,
                    progressServerDecayExp: 3.5
                });
                jQuery("#w0").fileupload("option", "done").call(fupload, $.Event("done"), {result: {files: files}});');
                $this->registerJs($js);
            }
            ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Next Step' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
