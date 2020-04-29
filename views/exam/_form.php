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
use kartik\switchinput\SwitchInput;
use app\assets\FormAsset;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\forms\ExamForm */
/* @var $form yii\widgets\ActiveForm */

FormAsset::register($this);

$exam = $model->exam;

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

// load default settings if there are some
if (count($model->examSettings) == 0
    && $exam->isNewRecord
    && empty(Yii::$app->request->post())
) {
    $ExamSettings = $model->defaultExamSettings;
} else {
    $ExamSettings = $model->examSettings;
}

$id = count($ExamSettings);
$setting_k = isset($id) ? str_replace('new', '', $id) : 0;
$this->registerJs('var setting_k = ' . $setting_k . ';', $this::POS_HEAD);
$url = \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'settings']);
$placeholder = \Yii::t('exams', 'Choose a setting ...');
$js = <<< SCRIPT

function format_rt(state) {
    return $('<div><div>...</div><span><b>' + state.text + '</b><br>' + state.hint + '</span></div>');
}

select2_config = {
    id: "ExamSettings_{$id}_key",
    name: "ExamSettings[$id][key]",
    theme: 'krajee',
    dropdownCssClass: "bigdrop",
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
    templateResult: format_rt,
    templateSelection: function (q) { return q.text; },
};

// new setting button
$('#exam-new-setting-button').on('click', function (event) {
    event.preventDefault();
    setting_k += 1;

    $('#exam_setting').prepend($('#exam-new-setting-block').html().replace(/__id__/g, 'new' + setting_k));
    $(".itemnew" + setting_k).find("select").select2(select2_config);
    $(".itemnew" + setting_k).find("select").on('select2:select select2:unselect', selected);

    $('#keyModal').modal('show');
    $.pjax({url: this.href, container: '#keyModalContent', push: false, async:false})
});

selected = function (e) {
    var data = e.params.data;
    var id = $(e.target).attr('data-id');

    if (e.type == 'select2:select') {

        // hide the element with the key input field in it
        $(e.target).closest('.key').addClass('hidden');

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
        <?= $step != 1 ? '<li>' . Html::a(
                '<i class="glyphicon glyphicon-file"></i> ' . \Yii::t('exams', 'Exam File'),
                '#file',
                ['data-toggle' => 'tab']
            ) . 
        '</li>' : '' ?>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-cog"></i> ' . \Yii::t('exams', 'Settings'),
                '#settings',
                ['data-toggle' => 'tab']
            ) ?>
        </li>
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
    
    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'settings',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <br>

    <div class="panel panel-warning">
        <div class="panel-heading">
            <i class="glyphicon glyphicon-warning-sign"></i> <?= \Yii::t('exams', 'Please notice, all the settings below will <b>override</b> the settings configured in the <b>exam file</b>!') ?>
            <?= Html::a('<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>&nbsp;' . \Yii::t('exams', 'New Setting'),
                Url::to([
                    'exam/index',
                    'mode' => 'list',
                    'attr' => 'settings2'
                ]),
                [
                    'id' => 'exam-new-setting-button', 
                    'class' => 'pull-right btn btn-success btn-xs'
                ]
            ); ?>
        </div>
        <div class="panel-body">
            <div class="row">
                <div id="exam_setting" class="col-md-12">
                    <?php
                    // existing setting fields
                    foreach ($ExamSettings as $id => $_setting) {
                        if ($_setting->detail === null || $_setting->detail->belongs_to === null) {
                            $id = $_setting->isNewRecord
                                ? (strpos($id, 'new') !== false
                                    ? $id
                                    : 'new' . $id)
                                : ($_setting->exam_id === null
                                    ? 'new' . $id
                                    : $_setting->id);
                            echo $this->render('_form_exam_setting', [
                                'id' => $id,
                                'form' => $form,
                                'setting' => $_setting,
                            ]);
                        }
                    }
                    ?>
                    <div id="exam-new-setting-block" style="display:none;">
                    <?= $this->render('_form_exam_setting', [
                        'id' => '__id__',
                        'form' => $form,
                        'setting' => $setting,
                    ]); ?>
                    </div>
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
            <i class="glyphicon glyphicon-warning-sign"></i> <?= \Yii::t('exams', 'The following settings should only be used, if you know what you are doing!') ?>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($exam, 'grp_netdev')->widget(SwitchInput::classname(), [
                        'pluginOptions' => [
                            'size' => 'mini',
                            'onText' => \Yii::t('app', 'ON'),
                            'offText' => \Yii::t('app', 'OFF'),
                            'onColor' => 'danger',
                            'offColor' => 'success',
                        ],
                        'options' => [
                            'label' => $exam->getAttributeLabel('grp_netdev')
                        ],
                    ])->label(false); ?>
                    <?= $form->field($exam, 'allow_sudo')->widget(SwitchInput::classname(), [
                        'pluginOptions' => [
                            'size' => 'mini',
                            'onText' => \Yii::t('app', 'ON'),
                            'offText' => \Yii::t('app', 'OFF'),
                            'onColor' => 'danger',
                            'offColor' => 'success',
                        ],
                        'options' => [
                            'label' => $exam->getAttributeLabel('allow_sudo')
                        ],
                    ])->label(false); ?>
                    <?= $form->field($exam, 'allow_mount')->widget(SwitchInput::classname(), [
                        'pluginOptions' => [
                            'size' => 'mini',
                            'onText' => \Yii::t('app', 'ON'),
                            'offText' => \Yii::t('app', 'OFF'),
                            'onColor' => 'danger',
                            'offColor' => 'success',
                        ],
                        'options' => [
                            'label' => $exam->getAttributeLabel('allow_mount')
                        ],
                    ])->label(false); ?>
                    <?= $form->field($exam, 'firewall_off')->widget(SwitchInput::classname(), [
                        'pluginOptions' => [
                            'size' => 'mini',
                            'onText' => \Yii::t('app', 'ON'),
                            'offText' => \Yii::t('app', 'OFF'),
                            'onColor' => 'danger',
                            'offColor' => 'success',
                        ],
                        'options' => [
                            'label' => $exam->getAttributeLabel('firewall_off')
                        ],
                    ])->label(false); ?>
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

    <?php

    Modal::begin([
        'id' => 'keyModal',
        'header' => '<h4>Title</h4>',
        //'footer' => Html::Button(\Yii::t('exam', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
        'size' => \yii\bootstrap\Modal::SIZE_LARGE,
    ]);

        Pjax::begin([
            'id' => 'keyModalContent',
            'enablePushState' => false,
        ]);
        Pjax::end();

    Modal::end();

    ?>

</div>
