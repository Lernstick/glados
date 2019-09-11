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

?>

<div class="auth-form">

    <?php $form = ActiveForm::begin(); ?>

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
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'domain')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <hr>

    <div class="panel panel-default">
        <div class="panel-heading">
            <?= Html::label($model->attributeLabels()['mapping']); ?>
            <div class="hint-block"><?= $model->attributeHints()['mapping']; ?></div>
        </div>
        <div class="panel-body">

            <div class="row">
                <div class="col-lg-5">
                    <div class="panel panel-info form-horizontal">
                        <div class="panel-heading">
                            <i class="glyphicon glyphicon-user"></i> <?= Html::label($model->attributeLabels()['query_login']); ?>
                            <div class="hint-block"><?= $model->attributeHints()['query_login']; ?></div>
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <?= Html::label($model->attributeLabels()['query_username'], 'query_username', [
                                    'class' => 'col-lg-4 control-label',
                                ]); ?>
                                <div class="col-lg-8">
                                    <?= Html::textInput('query_username', null, ['class' => 'form-control']); ?>
                                </div>
                                <div class="col-lg-4"></div>
                                <div class="col-lg-8"><p class="help-block help-block-error"></p></div>
                            </div>

                            <div class="form-group">
                                <?= Html::label($model->attributeLabels()['query_password'], 'query_password', [
                                    'class' => 'col-lg-4 control-label',
                                ]); ?>
                                <div class="col-lg-8">
                                    <?= Html::textInput('query_password', null, ['class' => 'form-control']); ?>
                                </div>
                                <div class="col-lg-4"></div>
                                <div class="col-lg-8"><p class="help-block help-block-error"></p></div>
                            </div>

                            <div class="form-group">
                                <div class="col-lg-offset-1 col-lg-11">
                                    <?= Html::submitButton(\Yii::t('auth', 'Test'), ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 form-group">
                    <?= Select2::widget([
                        'name' => 'todo',
                        'options' => [
                            'placeholder' => \Yii::t('auth', 'Choose Active Directory Groups ...'),
                            'multiple' => true,
                        ],
                        //'data' => ['test' => 'testlabel'],
                        'value' => array_keys($model->mapping, "admin"),
                        'data' => array_combine(array_keys($model->mapping, "admin"), array_keys($model->mapping, "admin")),
                        'maintainOrder' => true,
                        'showToggleAll' => true,
                        'addon' => [
                            'prepend' => ['content' => 'AD Groups'],
                            'append' => ['content' => '<i class="glyphicon glyphicon-arrow-right"></i>'
                                . \Yii::t('auth', '{groups} should be mapped to the role <code>{role}</code>', [
                                        'groups' => '',
                                        'role' => 'admin'
                                    ])]
                        ],
                        'pluginOptions' => [
                            'tags' => true,
                            'allowClear' => true,
                        ],
                    ]); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 form-group">
                    <?= Select2::widget([
                        'name' => 'todo',
                        'options' => [
                            'placeholder' => \Yii::t('auth', 'Choose Active Directory Groups ...'),
                            'multiple' => true,
                        ],
                        //'data' => ['test' => 'testlabel'],
                        'value' => array_keys($model->mapping, "teacher"),
                        'data' => array_combine(array_keys($model->mapping, "teacher"), array_keys($model->mapping, "teacher")),
                        'maintainOrder' => true,
                        'showToggleAll' => true,
                        'addon' => [
                            'prepend' => ['content' => 'AD Groups'],
                            'append' => ['content' => '<i class="glyphicon glyphicon-arrow-right"></i>'
                                . \Yii::t('auth', '{groups} should be mapped to the role <code>{role}</code>', [
                                        'groups' => '',
                                        'role' => 'teacher'
                                    ])]
                        ],
                        'pluginOptions' => [
                            'tags' => true,
                            'allowClear' => true,
                        ],
                    ]); ?>
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
            <i class="glyphicon glyphicon-warning-sign"></i> <?= \Yii::t('auth', 'The following settings should only be used, if you know what you are doing!') ?>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'ldap_uri')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'loginScheme')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'bindScheme')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'searchFilter')->textInput(['maxlength' => true]) ?>
                </div>
            </div>
        </div>
    </div>

    <?php Pjax::end(); ?>

    </div>
    <hr>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? \Yii::t('auth', 'Create') : \Yii::t('auth', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
