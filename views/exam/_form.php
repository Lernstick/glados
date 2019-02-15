<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;
//use kartik\file\FileInput;
use yii\web\JsExpression;
use limion\jqueryfileupload\JQueryFileUpload;
use kartik\range\RangeInput;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */
/* @var $form yii\widgets\ActiveForm */

if(($model->file && Yii::$app->file->set($model->file)->exists) || ($model->file2 && Yii::$app->file->set($model->file2)->exists)) {
    $this->registerJs('var files = [];');
}

if($model->file && Yii::$app->file->set($model->file)->exists) {
    $this->registerJs(new JsExpression("
        files.push({
            'name':'" . basename($model->file) . "',
            'size':" . filesize($model->file) . ",
            'deleteUrl':'" . Url::to(['delete', 'id' => $model->id, 'mode' => 'file', 'type' => 'squashfs']) . "',
            'deleteType':'POST'
        });
    "));
}

if($model->file2 && Yii::$app->file->set($model->file2)->exists) {
    $this->registerJs(new JsExpression("
        files.push({
            'name':'" . basename($model->file2) . "',
            'size':" . filesize($model->file2) . ",
            'deleteUrl':'" . Url::to(['delete', 'id' => $model->id, 'mode' => 'file', 'type' => 'zip']) . "',
            'deleteType':'POST'
        });
    "));
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

// add custom validation to the process queue of fileupload
$.blueimp.fileupload.prototype.options.processQueue.push(
    {
        action: "validateFiles",
        acceptFileTypes: "@",
        maxFileSize: "@",
        minFileSize: "@",
        maxNumberOfFiles: "@",
        disabled: "@disableValidation"
    }
);
$.widget("blueimp.fileupload", $.blueimp.fileupload, {
    processActions: {
        validateFiles: function (data, options) {
            var currentFile = data.files[data.index];
            if (currentFile.error === true) {
                return;
            }
            var files = new Array();
            // get all files
            $('#fileupload-exam tbody.files td span.name').each(function() {
                var name = $(this).html();
                files.push(name);
            });
            //files.push(currentFile.name);

            // get number of squashfs's and zip's
            sqs = files.filter(function(val) { return val.split('.').pop() === 'squashfs'; }).length;
            zips = files.filter(function(val) { return val.split('.').pop() === 'zip'; }).length;

            dfd = $.Deferred();
            if ((sqs > 1 || zips > 1) && currentFile.name != files[0]) {
                data.files[data.index].error = 'Only one file of each type (zip and squashfs) is allowed.';
                data.files.error = true;
                dfd.rejectWith(this, [data]);
            } else {
                dfd.resolveWith(this, [data]);
            }
            return dfd.promise();
        }
    }
});

SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);

$active_tabs = <<<JS
// Change hash for page-reload
$('.nav-tabs a').on('shown.bs.tab', function (e) {
    var prefix = "tab_";
    window.location.hash = e.target.hash.replace("#", "#" + prefix);
});

// Javascript to enable link to tab
$(window).bind('hashchange', function() {
    var prefix = "tab_";
    $('.nav-tabs a[href*="' + document.location.hash.replace(prefix, "") + '"]').tab('show');
}).trigger('hashchange');
JS;
$this->registerJs($active_tabs);


$js = <<< 'SCRIPT'
$("input[name='Exam[libre_autosave]']").click(function(){
    if ($(this).is(':checked')) {                    
        $('#exam-libre_autosave_interval').attr("disabled", false);               
    } else if ($(this).not(':checked')) {                          
        $('#exam-libre_autosave_interval').attr("disabled", true);                            
    }
});
$("input[name='Exam[screenshots]']").click(function(){
    if ($(this).is(':checked')) {                    
        $('#exam-screenshots_interval').attr("disabled", false);               
    } else if ($(this).not(':checked')) {                          
        $('#exam-screenshots_interval').attr("disabled", true);                            
    }
});
SCRIPT;
$this->registerJs($js);

?>

<div class="exam-form">

    <?php $form = $step == 0 || $step == 2 ? ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
    ]) : ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
        'action' => ['create', '#' => 'file']
    ]); ?>

    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#general">
            <i class="glyphicon glyphicon-home"></i>
            General
        </a></li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-book"></i> Libreoffice',
                '#libreoffice',
                ['data-toggle' => 'tab']
            ) ?>
        </li>
        <?= $step != 1 ? '<li>' . Html::a(
                '<i class="glyphicon glyphicon-file"></i> Exam File',
                '#file',
                ['data-toggle' => 'tab']
            ) . 
        '</li>' : '' ?>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-exclamation-sign"></i> Expert Settings',
                '#expert',
                ['data-toggle' => 'tab']
            ) ?>
        </li>
    </ul>

    <div class="tab-content">

    <?php Pjax::begin([
        'id' => 'general',
        'options' => ['class' => 'tab-pane fade in active'],
    ]); ?>

    <br>

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

    <hr>

    <div class="panel panel-warning">
        <div class="panel-heading">
            <i class="glyphicon glyphicon-warning-sign"></i> Please notice, all the settings below will <b>override</b> the settings configured in the <b>exam file</b>!
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6" style="width:auto;">
                            <?= $form->field($model, 'screenshots')->checkbox() ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'screenshots_interval', [
                                'template' => '{label}<div class="input-group"><div class="input-group-addon">with Interval of</div>{input}<span class="input-group-addon" id="basic-addon2">minutes</span></div>{hint}{error}'
                            ])->textInput(['type' => 'number', 'disabled' => !$model->screenshots])->label(false); ?>
                        </div>                
                        <div class="col-md-12">
                            <?= $form->field($model, 'max_brightness')->widget(RangeInput::classname(), [
                                'options' => ['placeholder' => 'Select range ...'],
                                'html5Options' => ['min'=>0, 'max'=>100, 'step'=>1],
                                'addon' => ['append'=>['content'=>'%']]
                            ]) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'url_whitelist')->textarea([
                        'rows' => '6',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'libreoffice',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <br>
    <div class="panel panel-warning">
        <div class="panel-heading">
            <i class="glyphicon glyphicon-warning-sign"></i> Please notice, all the settings below will <b>override</b> the settings configured in the <b>exam file</b>!
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6" style="width:auto;">
                    <?= $form->field($model, 'libre_autosave')->checkbox() ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'libre_autosave_interval', [
                        'template' => '{label}<div class="input-group"><div class="input-group-addon">with Interval of</div>{input}<span class="input-group-addon" id="basic-addon2">minutes</span></div>{hint}{error}'
                    ])->textInput(['type' => 'number', 'disabled' => !$model->libre_autosave])->label(false); ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'libre_createbackup')->checkbox() ?>
                </div>
            </div>
        </div>
    </div>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'expert',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <br>
    <div class="panel panel-danger">
        <div class="panel-heading">
            <i class="glyphicon glyphicon-warning-sign"></i> The following settings should only be used, if you know what you are doing!
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'grp_netdev')->checkbox() ?>
                    <?= $form->field($model, 'allow_sudo')->checkbox() ?>
                    <?= $form->field($model, 'allow_mount')->checkbox() ?>
                    <?= $form->field($model, 'firewall_off')->checkbox() ?>
                </div>
                <div class="col-md-6">
                </div>
            </div>
        </div>
    </div>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'file',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <br>
    <div class="panel panel-default">
        <div class="panel-heading">
            <?= Html::Label('Exam Image files'); ?>
            <?= Html::activeHint($model, 'file', ['class' => 'hint-block'])?>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12" id="fileupload-exam">
                    <?php
                    if(!$model->isNewRecord) {

                        //echo Html::activeLabel($model, 'file');
                        echo JQueryFileUpload::widget([
                            'model' => $model,
                            'name' => 'file',
                            'url' => ['update', 'id' => $model->id, 'mode' => 'upload'],
                            'appearance' => 'ui', // available values: 'ui','plus' or 'basic'
                            'mainView'=>'@app/views/exam/_upload_main',
                            'uploadTemplateView'=>'@app/views/exam/_upload_upload',
                            'downloadTemplateView'=>'@app/views/exam/_upload_download',
                            'formId' => $form->id,
                            'options' => [
                                'multiple' => true,
                                'sequentialUploads' => true
                            ],
                            'clientOptions' => [
                                'maxFileSize' => 4000000000,
                                'dataType' => 'json',
                                'acceptFileTypes' => new yii\web\JsExpression('/(\.|\/)(squashfs|zip)$/i'),
                                'maxNumberOfFiles' => 2,
                                'autoUpload' => true,
                            ],
                        ]);
                        echo "<hr>";
                    }
                    ?>

                    <?php
                    if(($model->file && Yii::$app->file->set($model->file)->exists) || ($model->file2 && Yii::$app->file->set($model->file2)->exists)) {
                        $js = new JsExpression('var fupload = jQuery("#w0").fileupload({
                            "maxFileSize":4000000000,
                            "dataType":"json",
                            "acceptFileTypes":/(\.|\/)(squashfs|zip)$/i,
                            "maxNumberOfFiles":2,
                            "autoUpload":true,
                            "sequentialUploads": true,
                            "url":' . json_encode(Url::to(['update', 'id' => $model->id, 'mode' => 'upload']), JSON_HEX_AMP) . ',
                            progressServerRate: 0.5,
                            progressServerDecayExp: 3.5
                        });
                        jQuery("#w0").fileupload("option", "done").call(fupload, $.Event("done"), {result: {files: files}});
                        ');
                        $this->registerJs($js);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <?php Pjax::end(); ?>

    </div>
    <hr>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Next Step' : ($step == 2 ? 'Finish' : 'Apply'), ['class' => $model->isNewRecord || $step == 2 ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
