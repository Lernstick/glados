<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Auth */
/* @var $form yii\widgets\ActiveForm */

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
JS;

$this->registerJs($js);

?>

<div class="auth-form">

    <?php $form = ActiveForm::begin(['id' => 'ldap_form_migrate']); ?>

    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#general">
            <i class="glyphicon glyphicon-home"></i>
            <?= \Yii::t('auth', 'General') ?>
        </a></li>
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
        <div class="col-lg-5">
            <div class="panel panel-info form-horizontal">
                <div class="panel-heading">
                    <i class="glyphicon glyphicon-user"></i> <?= Html::label($model->attributeLabels()['query_login']); ?>
                    <div class="hint-block"><?= $model->attributeHints()['query_login']; ?></div>
                </div>
                <div class="panel-body">
                    <?= $form->field($model, 'query_username', [
                        'template' => "{label}\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                    ]) ?>

                    <?= $form->field($model, 'query_password', [
                        'template' => "{label}\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                    ])->passwordInput() ?>

                    <div class="form-group">
                        <div class="col-lg-offset-1 col-lg-11">
                            <?= Html::submitButton(\Yii::t('auth', 'Query for Usernames'), ['class' => 'btn btn-primary', 'name' => 'query-users-button', 'id' => 'query-users-button']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="help-block" style="height:200px; overflow: auto;"><?= implode("<br>", $model->debug); ?></div>
            <div class="has-error"><div class="help-block"><?= $model->error; ?></div></div>
            <div class="has-success"><div class="help-block"><?= $model->success; ?></div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 help-block">
            <?= \Yii::t('auth', 'The following local users where also found in the LDAP Directory. Selected users will be migrated from local to the authentication method {method}.', [
                'method' => $model->name,
            ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 form-group">
            <?= Select2::widget([
                'name' => 'migrate',
                'options' => [
                    'placeholder' => \Yii::t('auth', 'Choose Local Users to migrate ...'),
                    'multiple' => true,
                ],
                'value' => array_keys($model->migrateUsers),
                'data' => $model->migrateUsers,
                'maintainOrder' => true,
                'showToggleAll' => true,
                'addon' => [
                    'prepend' => ['content' => \Yii::t('auth', 'Local Users')],
                    'append' => ['content' => '<i class="glyphicon glyphicon-arrow-right"></i>'
                        . \Yii::t('auth', '{users} will be migrated to the authentication method {method}', [
                                'users' => '',
                                'method' => ''
                            ])],
                    'contentAfter' => '<span class="input-group-addon" style="background-color:white; width:100px;">' . $model->name . '</span>',
                ],
                'pluginOptions' => [
                    'tags' => true,
                    'allowClear' => true,
                    'language' => [
                        'noResults' => new JsExpression('function (params) { return "' . \Yii::t('auth', 'No users found, provide credentials to fill this dropdown list.') . '"; }'),
                    ],
                ],
            ]); ?>
        </div>
    </div>

    <hr>
    <?= $form->field($model, 'scenario')->hiddenInput()->label(false)->hint(false) ?>

    <div class="form-group">
        <?= Html::submitButton(\Yii::t('auth', 'Apply'), ['class' => 'btn btn-primary', 'id' => 'submit-button', 'name' => 'submit-button']) ?>
    </div>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'expert',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <br>
    <div class="panel panel-danger">
        <div class="panel-heading">
            <i class="glyphicon glyphicon-warning-sign"></i> <?= \Yii::t('auth', 'The following settings should only be used, if you know what you are doing!') ?>
        </div>
        <div class="panel-body">
            <div class="row">

                <div class="col-md-6">
                    <?= $form->field($model, 'migrateSearchScheme')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'userIdentifier')->widget(Select2::classname(), [
                        'data' => array_merge([$model->userIdentifier => $model->userIdentifier], array_combine($model->identifierAttributes, $model->identifierAttributes)),
                        'options' => [
                            'placeholder' => \Yii::t('auth', 'Select an attribute ...'),
                        ],
                        'pluginOptions' => [
                            'tags' => true,
                            'allowClear' => false
                        ],
                    ]); ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'migrateUserSearchFilter')->textInput(['maxlength' => true]) ?>
                </div>
            </div>
        </div>
    </div>

    <?php Pjax::end(); ?>

    </div>

    <?php ActiveForm::end(); ?>

</div>
