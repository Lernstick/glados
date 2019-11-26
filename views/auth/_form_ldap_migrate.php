<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Auth */
/* @var $searchmodel app\models\AuthSearch */
/* @var $form yii\widgets\ActiveForm */

$from = $model->fromModel;
$to = $model->toModel;

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

$js = <<< JS
// set the scenario and reset the form errors
$('#submit-button').on('click', function(e) {
    $('#ldap-scenario').val('migrate');
    $("#ldap_form").yiiActiveForm('resetForm');
});

$('#query-users-button').on('click', function(e) {
    $('#ldap-scenario').val('query_users');
    $("#ldap_form").yiiActiveForm('resetForm');
});

$('#copy-credentials-button').on('click', function(e) {
    bindUsername = $("input[name^='Auth'][name$='[bindUsername]']");
    bindPassword = $("input[name^='Auth'][name$='[bindPassword]']");
    queryUsername = $("input[name^='Auth'][name$='[query_username]']");
    queryPassword = $("input[name^='Auth'][name$='[query_password]']");
    queryUsername.val(bindUsername.val());
    queryPassword.val(bindPassword.val());
});

JS;

$this->registerJs($js);

?>

<div class="auth-form">

    <div class="panel panel-info">
        <div class="panel-heading">
            <?= Html::label(\Yii::t('auth', 'Query for users')) ?>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model->toModel, 'migrateSearchPattern')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model->toModel, 'migrateUserSearchFilter')->textInput(['maxlength' => true]) ?>
                </div>
            </div>
            <div class="row">
                <?= $form->field($model->toModel, 'bindUsername')->hiddenInput()->label(false)->hint(false) ?>
                <?= $form->field($model->toModel, 'bindPassword')->hiddenInput()->label(false)->hint(false) ?>
                <div class="col-lg-5">
                    <?= $form->field($model->toModel, 'query_username', [
                        'template' => "{label}\n<div class='col-lg-8'><div class='input-group'>{input}<span class='input-group-btn'>{button}</span></div></div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                        'parts' => [
                            'button' => Html::button('<span class="glyphicon glyphicon-copy" aria-hidden="true"></span>', [
                                'class' => 'btn btn-default',
                                'title' => \Yii::t('auth', 'Copy credentials from {field}', [
                                    'field' => \Yii::t('auth', 'Bind credentials'),
                                ]),
                                'name' => 'copy-credentials-button',
                                'id' => 'copy-credentials-button'])
                        ],
                    ]) ?>

                    <?= $form->field($model->toModel, 'query_password', [
                        'template' => "{label}\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                    ])->passwordInput() ?>

                    <div class="form-group">
                        <div class="col-lg-offset-1 col-lg-11">
                            <?= Html::submitButton(\Yii::t('auth', 'Query for Users'), ['class' => 'btn btn-primary', 'name' => 'query-users-button', 'id' => 'query-users-button']) ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="help-block" style="height:200px; overflow: auto;"><?= implode("<br>", $to->debug); ?></div>
                    <div class="has-error"><div class="help-block"><?= $to->error; ?></div></div>
                    <div class="has-success"><div class="help-block"><?= $to->success; ?></div></div>
                </div>
            </div>
        </div>
    </div>

</div>