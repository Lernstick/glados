<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\models\ExamSetting;
use app\models\ExamSettingAvail;
use yii\widgets\Pjax;
use kartik\select2\Select2;
use yii\web\JsExpression;
use limion\jqueryfileupload\JQueryFileUpload;
use kartik\range\RangeInput;

/* @var $this yii\web\View */
/* @var $model app\models\forms\ExamForm */
/* @var $form yii\widgets\ActiveForm */

$exam = $model->exam;
$screenCapture = $model->screenCapture;

$setting = new ExamSetting();
$setting->loadDefaultValues();

if(($exam->file && Yii::$app->file->set($exam->file)->exists) || ($exam->file2 && Yii::$app->file->set($exam->file2)->exists)) {
    $this->registerJs('var files = [];');
}

if($exam->file && Yii::$app->file->set($exam->file)->exists) {
    $this->registerJs(new JsExpression("
        files.push({
            'name':'" . basename($exam->file) . "',
            'size':" . filesize($exam->file) . ",
            'deleteUrl':'" . Url::to(['delete', 'id' => $exam->id, 'mode' => 'file', 'type' => 'squashfs']) . "',
            'deleteType':'POST'
        });
    "));
}

if($exam->file2 && Yii::$app->file->set($exam->file2)->exists) {
    $this->registerJs(new JsExpression("
        files.push({
            'name':'" . basename($exam->file2) . "',
            'size':" . filesize($exam->file2) . ",
            'deleteUrl':'" . Url::to(['delete', 'id' => $exam->id, 'mode' => 'file', 'type' => 'zip']) . "',
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
SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);

$js = <<< 'SCRIPT'
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
// file upload client side validation
if (!$exam->isNewRecord) {
    $this->registerJs($js);
}

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
        $('#exam-libre_autosave_path').attr("disabled", false);
    } else if ($(this).not(':checked')) {
        $('#exam-libre_autosave_interval').attr("disabled", true);
        $('#exam-libre_autosave_path').attr("disabled", true);
    }
});
$("input[name='Exam[libre_createbackup]']").click(function(){
    if ($(this).is(':checked')) {
        $('#exam-libre_createbackup_path').attr("disabled", false);
    } else if ($(this).not(':checked')) {
        $('#exam-libre_createbackup_path').attr("disabled", true);
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


$id = count($model->examSettings);
$setting_k = isset($id) ? str_replace('new', '', $id) : 0;
$this->registerJs('var setting_k = ' . $setting_k . ';');
$url = \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'settings']);
$placeholder = \Yii::t('exams', 'Choose a setting ...');
$js = <<< SCRIPT
select2_config = {
    id: "ExamSettings_{$id}_key",
    name: "ExamSettings[$id][key]",
    theme: 'krajee',
    dropdownAutoWidth: true,
    width: 'auto',
    allowClear: true,
    placeholder: "{$placeholder}",
    ajax: {
        url: "{$url}",
        dataType: 'json',
        delay: 250,
        cache: true,
        data: function (params) {
            return {
                q: params.term,
                page: params.page,
                per_page: 10
            };
        },
        processResults: function(data, page) {
            data.results.forEach(function(el, idx){
                var self = this;
                $("[id^=ExamSettings_][id$=_key]").each(function (){
                    if ($(this).attr('id') != "ExamSettings___id___key") {
                        if ($(this).find(':selected').attr('value') == self[idx].id) {
                            self[idx].disabled = true;
                        }
                    }
                })
            }, data.results);

            return {
                results: data.results,
                pagination: {
                    more: data.results.length === 10
                }
            };
        },
    },
    escapeMarkup: function (markup) { return markup; },
    templateResult: function(q) { return q.text; },
    templateSelection: function (q) { return q.text; },
};

// new setting button
$('#exam-new-setting-button').on('click', function () {
    setting_k += 1;

    $('#exam_setting').append($('#exam-new-setting-block').html().replace(/__id__/g, 'new' + setting_k));
    $(".itemnew" + setting_k).find("select").select2(select2_config);
    $(".itemnew" + setting_k).find("select").on('select2:select select2:unselect', selected);
});

selected = function (e) {
    var data = e.params.data;
    var id = $(e.target).attr('data-id');

    if (e.type == 'select2:select') {
        $(e.target).parent().find('label').next('a').remove();
        $(e.target).parent().find('label').after('&nbsp<a tabindex="0" role="button" class="hint glyphicon glyphicon-question-sign"></a>');

        $(e.target).parent().find('a.hint').popover({
            html: true,
            trigger: 'focus',
            placement: 'right',
            title:  e.params.data.text,
            toggle: 'popover',
            container: 'body',
            content: e.params.data.hint
        });

        $.pjax.reload({
            container: "#item" + id,
            fragment: "body",
            type: 'POST',
            data: {
                'setting[id]': id,
                'setting[key]': e.params.data.id,
                '_csrf': $("input[name='_csrf']").val()
            },
            async:true
        });
    } else if (e.type == 'select2:unselect') {
        $(e.target).parent().find('label').next('a').remove();

        $.pjax.reload({
            container: "#item" + id,
            fragment: "body",
            type: 'POST',
            data: {
                'setting[id]': '__id__',
                'setting[key]': 'default',
                '_csrf': $("input[name='_csrf']").val()
            },
            async:true
        });
    }
}

// remove setting button
$(document).on('click', '.exam-remove-setting-button', function () {
    $(this).closest('div.item').remove();
});

// activate select2 for all existing elements except of the template element
$("[id^=ExamSettings_][id$=_key]").each(function (){
    if ($(this).attr('id') != "ExamSettings___id___key") {
        $(this).select2(select2_config);
        $(this).on('select2:select select2:unselect', selected);
    }
})

SCRIPT;
$this->registerJs($js);

?>
<div class="exam-form">

    <?php $form = $step == 0 || $step == 2 ? ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
        'enableClientValidation' => false,
    ]) : ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
        'action' => ['create', '#' => 'file'],
        'enableClientValidation' => false,
    ]); ?>

    <?= $model->errorSummary($form); ?>

    <div style="display:none;">
        <?= $form->field($exam, 'id')->widget(Select2::classname(), []); ?>
    </div>

    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#general">
            <i class="glyphicon glyphicon-home"></i>
            <?= \Yii::t('exams', 'General') ?>
        </a></li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-book"></i> ' . \Yii::t('exams', 'Libreoffice'),
                '#libreoffice',
                ['data-toggle' => 'tab']
            ) ?>
        </li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-camera"></i> ' . \Yii::t('exams', 'Screen Capture'),
                '#screen_capture',
                ['data-toggle' => 'tab']
            ) ?>
        </li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-cog"></i> ' . \Yii::t('exams', 'Settings'),
                '#settings',
                ['data-toggle' => 'tab']
            ) ?>
        </li>
        <?= $step != 1 ? '<li>' . Html::a(
                '<i class="glyphicon glyphicon-file"></i> ' . \Yii::t('exams', 'Exam File'),
                '#file',
                ['data-toggle' => 'tab']
            ) . 
        '</li>' : '' ?>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-exclamation-sign"></i> ' . \Yii::t('exams', 'Expert Settings'),
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
            <?= $form->field($exam, 'name')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($exam, 'subject')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($exam, 'time_limit', [
                'template' => '{label}<div class="input-group">{input}<span class="input-group-addon" id="basic-addon2">' . \Yii::t('exams', 'minutes') . '</span></div>{hint}{error}'
            ])->textInput(['type' => 'number']); ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($exam, 'backup_path')->textInput(['maxlength' => true]) ?>
        </div>        
    </div>

    <hr>

    <div class="panel panel-warning">
        <div class="panel-heading">
            <i class="glyphicon glyphicon-warning-sign"></i> <?= \Yii::t('exams', 'Please notice, all the settings below will <b>override</b> the settings configured in the <b>exam file</b>!') ?>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-12" style="width:auto;">
                            <?= $form->field($exam, 'screenshots')->checkbox() ?>
                        </div>
                        <div class="col-md-12">
                            <?= $form->field($exam, 'screenshots_interval', [
                                'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', 'with Interval of') . '</div>{input}<span class="input-group-addon" id="basic-addon2">' . \Yii::t('exams', 'minutes') . '</span></div>{hint}{error}'
                            ])->textInput(['type' => 'number', 'disabled' => !$exam->screenshots])->label(false); ?>
                        </div>                
                        <div class="col-md-12">
                            <?= $form->field($exam, 'max_brightness')->widget(RangeInput::classname(), [
                                'options' => ['placeholder' => \Yii::t('exams', 'Select range ...')],
                                'html5Options' => ['min' => 0, 'max' => 100, 'step' => 1],
                                'html5Container' => ['style' => 'width:80%'],
                                'addon' => ['append' => ['content' => '%']]
                            ]) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <?= $form->field($exam, 'url_whitelist')->textarea([
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
            <i class="glyphicon glyphicon-warning-sign"></i> <?= \Yii::t('exams', 'Please notice, all the settings below will <b>override</b> the settings configured in the <b>exam file</b>!') ?>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <?= $form->field($exam, 'libre_autosave', [
                                'options' => ['class' => ''],
                                'errorOptions' => ['tag' => false],
                            ])->checkbox() ?>
                        </div>
                        <div class="panel-body">
                            <?= $form->field($exam, 'libre_autosave_path', [
                                'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...to the directory') . '</div>{input}</div>{hint}{error}'
                            ])->textInput(['disabled' => !$exam->libre_autosave])->label(false); ?>
                            <?= $form->field($exam, 'libre_autosave_interval', [
                                'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...all {n} minutes.', [
                                        'n' => '</div>{input}<span class="input-group-addon" id="basic-addon2">'
                                    ]) . '</span></div>{hint}{error}'
                            ])->textInput(['type' => 'number', 'disabled' => !$exam->libre_autosave])->label(false); ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <?= $form->field($exam, 'libre_createbackup', [
                                'options' => ['class' => ''],
                                'errorOptions' => ['tag' => false],
                            ])->checkbox() ?>
                        </div>
                        <div class="panel-body">
                            <?= $form->field($exam, 'libre_createbackup_path', [
                                'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...to the directory') . '</div>{input}</div>{hint}{error}'
                            ])->textInput(['disabled' => !$exam->libre_createbackup])->label(false); ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'screen_capture',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <br>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($screenCapture, 'enabled')->checkbox() ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($screenCapture, 'quality')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'settings',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <br>

    <legend>Settings
        <?= Html::a('New Setting', 'javascript:void(0);', [
            'id' => 'exam-new-setting-button', 
            'class' => 'pull-right btn btn-default btn-xs'
        ]); ?>
    </legend>

    <div id="exam_setting">
        <?php
        // existing setting fields
        foreach ($model->examSettings as $id => $_setting) {
            if ($_setting->detail === null || $_setting->detail->belongs_to === null) {
                $id = $_setting->isNewRecord ? (strpos($id, 'new') !== false ? $id : 'new' . $id) : $_setting->id;
                $members = [];
                foreach ($model->examSettings as $s) {
                    if($id == $s->belongs_to) {
                        $members[] = $s;
                    }
                }
                echo $this->render('_form_exam_setting', [
                    'id' => $id,
                    'form' => $form,
                    'setting' => $_setting,
                    'members' => $members,
                ]);
            }
        }
        ?>
        <div id="exam-new-setting-block" style="display:none;">
        <?= $this->render('_form_exam_setting', [
            'id' => '__id__',
            'form' => $form,
            'setting' => $setting,
            'members' => [],
        ]); ?>
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
            <i class="glyphicon glyphicon-warning-sign"></i> <?= \Yii::t('exams', 'The following settings should only be used, if you know what you are doing!') ?>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($exam, 'grp_netdev')->checkbox() ?>
                    <?= $form->field($exam, 'allow_sudo')->checkbox() ?>
                    <?= $form->field($exam, 'allow_mount')->checkbox() ?>
                    <?= $form->field($exam, 'firewall_off')->checkbox() ?>
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
            <?= Html::Label(\Yii::t('exams', 'Exam Image files')); ?>
            <?= Html::activeHint($exam, 'file', ['class' => 'hint-block'])?>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12" id="fileupload-exam">
                    <?php
                    if(!$exam->isNewRecord) {

                        //echo Html::activeLabel($exam, 'file');
                        echo JQueryFileUpload::widget([
                            'model' => $exam,
                            'name' => 'file',
                            'url' => ['update', 'id' => $exam->id, 'mode' => 'upload'],
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
                    if(($exam->file && Yii::$app->file->set($exam->file)->exists) || ($exam->file2 && Yii::$app->file->set($exam->file2)->exists)) {
                        $js = new JsExpression('var fupload = jQuery("#w0").fileupload({
                            "maxFileSize":4000000000,
                            "dataType":"json",
                            "acceptFileTypes":/(\.|\/)(squashfs|zip)$/i,
                            "maxNumberOfFiles":2,
                            "autoUpload":true,
                            "sequentialUploads": true,
                            "url":' . json_encode(Url::to(['update', 'id' => $exam->id, 'mode' => 'upload']), JSON_HEX_AMP) . ',
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
        <?= Html::submitButton($exam->isNewRecord ? \Yii::t('exams', 'Next Step') : ($step == 2 ? \Yii::t('exams', 'Apply') : \Yii::t('exams', 'Apply')), ['class' => $exam->isNewRecord || $step == 2 ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
