<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;
use kartik\select2\Select2;
use yii\web\JsExpression;
use kartik\range\RangeInput;

/* @var $this yii\web\View */
/* @var $model app\models\Auth */
/* @var $searchModel app\models\UserSearch */
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
    $('#ldap-scenario').val('default');
    $("#ldap_form").yiiActiveForm('resetForm');
});

$('#query-groups-button').on('click', function(e) {
    $('#ldap-scenario').val('query_groups');
    $("#ldap_form").yiiActiveForm('resetForm');
});
JS;
$this->registerJs($js);

$js = <<< 'SCRIPT'
directBind = $("input[name^='Auth'][name$='[method][]'][value=bind_direct]")
userBind = $("input[name^='Auth'][name$='[method][]'][value=bind_byuser]")

if (directBind.is(':checked')) {
    $('#fields-user').hide()
}

if (userBind.is(':checked')) {
    $('#fields-direct').hide()
}

// this is the default, invoke by a click()
directBind.click(function(){
    if ($(this).is(':checked')) {
        userBind.prop("checked", false);
        $('#fields-direct').show()
        $('#fields-user').hide()
    } else if ($(this).not(':checked')) {
        userBind.prop("checked", true);
        $('#fields-direct').hide()
        $('#fields-user').show()
    }
});

userBind.click(function(){
    if ($(this).is(':checked')) {
        directBind.prop("checked", false);
        $('#fields-direct').hide()
        $('#fields-user').show()
    } else if ($(this).not(':checked')) {
        directBind.prop("checked", true);
        $('#fields-direct').hide()
        $('#fields-user').show()
    }
});

SCRIPT;
$this->registerJs($js);

?>

<div class="auth-form">

    <?php $form = ActiveForm::begin(['id' => 'ldap_form']); ?>

    <?= $form->errorSummary($model); ?>

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
            <?= $form->field($model, 'domain')->textInput(['maxlength' => true]); ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'order')->textInput([
                'type' => 'number',
                'value' => $model->order === null
                    ? max(array_column($model->fileConfig, 'order')) + 1
                    : $model->order,
            ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'loginScheme')->textInput(['maxlength' => true]) ?>
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
                                    <?= Html::submitButton(\Yii::t('auth', 'Query for LDAP Groups'), ['class' => 'btn btn-primary', 'name' => 'query-groups-button', 'id' => 'query-groups-button']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="help-block"><?= implode("<br>", $model->debug); ?></div>
                    <div class="has-error"><div class="help-block"><?= $model->error; ?></div></div>
                    <div class="has-success"><div class="help-block"><?= $model->success; ?></div></div>
                </div>
            </div>
            <?php
            foreach (array_keys($searchModel->roleList) as $key => $role) {
                ?><div class="row">
                    <div class="col-md-12 form-group">
                        <?= Select2::widget([
                            'name' => $model->formName() . '[mapping][' . $role . ']',
                            'options' => [
                                'placeholder' => \Yii::t('auth', 'Choose LDAP Groups ...'),
                                'multiple' => true,
                            ],
                            'value' => array_keys($model->mapping, $role),
                            //'data' => array_combine(array_keys($model->mapping), array_keys($model->mapping)),
                            'data' => $model->groups,
                            'maintainOrder' => true,
                            'showToggleAll' => true,
                            'addon' => [
                                'prepend' => ['content' => \Yii::t('auth', 'LDAP Groups')],
                                'append' => ['content' => '<i class="glyphicon glyphicon-arrow-right"></i>'
                                    . \Yii::t('auth', '{groups} will be mapped to the role {role}', [
                                            'groups' => '',
                                            'role' => ''
                                        ])],
                                'contentAfter' => '<span class="input-group-addon" style="background-color:white; width:100px;">' . $role . '</span>',
                            ],
                            'pluginOptions' => [
                                'tags' => true,
                                'allowClear' => true,
                                'language' => [
                                    'noResults' => new JsExpression('function (params) { return "' . \Yii::t('auth', 'No groups found, provide credentials to fill this dropdown list.') . '"; }'),
                                ],
                            ],
                        ]); ?>
                    </div>
                </div><?php

            }

            ?>

        </div>
    </div>

    <hr>
    <?= $form->field(new \app\models\Auth(['class' => $model->class]), 'class')->hiddenInput()->label(false)->hint(false) ?>
    <?= $form->field($model, 'scenario')->hiddenInput()->label(false)->hint(false) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? \Yii::t('auth', 'Create') : \Yii::t('auth', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary', 'id' => 'submit-button', 'name' => 'submit-button']) ?>
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
                <div class="col-md-12">
                    <?= $form->field($model, 'ldap_uri')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'userSearchFilter')->widget(Select2::classname(), [
                        'data' => array_merge([$model->userSearchFilter => $model->userSearchFilter], $model->userSearchFilterList),
                        'options' => [
                            'placeholder' => \Yii::t('auth', 'Select a search filter ...'),
                        ],
                        'pluginOptions' => [
                            'tags' => true,
                            'allowClear' => false
                        ],
                    ]); ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'uniqueIdentifier')->widget(Select2::classname(), [
                        'data' => array_merge([$model->uniqueIdentifier => $model->uniqueIdentifier], array_combine($model->identifierAttributes, $model->identifierAttributes)),
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
                    <?= $form->field($model, 'groupSearchFilter')->widget(Select2::classname(), [
                        'data' => array_merge([$model->groupSearchFilter => $model->groupSearchFilter], $model->groupSearchFilterList),
                        'options' => [
                            'placeholder' => \Yii::t('auth', 'Select a search filter ...'),
                        ],
                        'pluginOptions' => [
                            'tags' => true,
                            'allowClear' => false
                        ],
                    ]); ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'groupIdentifier')->widget(Select2::classname(), [
                        'data' => array_merge([$model->groupIdentifier => $model->groupIdentifier], array_combine($model->identifierAttributes, $model->identifierAttributes)),
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
                    <?= $form->field($model, 'groupMemberAttribute')->widget(Select2::classname(), [
                        'data' => array_merge([$model->groupMemberAttribute => $model->groupMemberAttribute], $model->groupMemberAttributeList),
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
                    <?= $form->field($model, 'groupMemberUserAttribute')->widget(Select2::classname(), [
                        'data' => array_merge([$model->groupMemberUserAttribute => $model->groupMemberUserAttribute], array_combine($model->identifierAttributes, $model->identifierAttributes)),
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
                    <?= $form->field($model, 'primaryGroupUserAttribute')->widget(Select2::classname(), [
                        'data' => array_merge([$model->primaryGroupUserAttribute => $model->primaryGroupUserAttribute], array_combine($model->identifierAttributes, $model->identifierAttributes)),
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
                    <?= $form->field($model, 'primaryGroupGroupAttribute')->widget(Select2::classname(), [
                        'data' => array_merge([$model->primaryGroupGroupAttribute => $model->primaryGroupGroupAttribute], array_combine($model->identifierAttributes, $model->identifierAttributes)),
                        'options' => [
                            'placeholder' => \Yii::t('auth', 'Select an attribute ...'),
                        ],
                        'pluginOptions' => [
                            'tags' => true,
                            'allowClear' => false
                        ],
                    ]); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <?php
                            $form->field($model, 'method')->begin();
                            echo Html::activeLabel($model,'method');
                            echo Html::activeHint($model,'method', ['class' => 'hint-block']);
                            $form->field($model, 'method')->end();
                            ?>
                        </div>
                        <div class="panel-body">
                            <?= $form->field($model, 'method', [
                                'options' => [
                                    'class' => '',
                                ],
                                'errorOptions' => ['tag' => false],
                            ])->checkboxList([
                                $model::SCENARIO_BIND_DIRECT => \Yii::t('auth', 'Bind directly by login credentials'),
                                $model::SCENARIO_BIND_BYUSER => \Yii::t('auth', 'Bind by given username and password')
                            ])->label(false)->hint(false) ?>

                            <div id="fields-direct">
                                <div class="col-md-12">
                                    <?= $form->field($model, 'bindScheme')->textInput(['maxlength' => true]) ?>
                                </div>
                                <div class="col-md-12">
                                    <?= $form->field($model, 'loginSearchFilter')->textInput(['maxlength' => true]) ?>
                                </div>
                            </div>

                            <div id="fields-user">
                                <div class="col-md-12">
                                    <?= $form->field($model, 'bindUsername', [
                                        'template' => "{label}\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                                    ]) ?>

                                    <?= $form->field($model, 'bindPassword', [
                                        'template' => "{label}\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                                    ])->passwordInput() ?>
                                </div>
                                <div class="col-md-12">
                                    <?= $form->field($model, 'loginAttribute')->widget(Select2::classname(), [
                                        'data' => array_merge([$model->loginAttribute => $model->loginAttribute], array_combine($model->identifierAttributes, $model->identifierAttributes)),
                                        'options' => [
                                            'placeholder' => \Yii::t('auth', 'Select an attribute ...'),
                                        ],
                                        'pluginOptions' => [
                                            'tags' => true,
                                            'allowClear' => false
                                        ],
                                    ]); ?>
                                </div>
                                <div class="col-md-12">
                                    <?= $form->field($model, 'bindAttribute')->widget(Select2::classname(), [
                                        'data' => array_merge([$model->bindAttribute => $model->bindAttribute], array_combine($model->identifierAttributes, $model->identifierAttributes)),
                                        'options' => [
                                            'placeholder' => \Yii::t('auth', 'Select an attribute ...'),
                                        ],
                                        'pluginOptions' => [
                                            'tags' => true,
                                            'allowClear' => false
                                        ],
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php Pjax::end(); ?>

    </div>

    <?php ActiveForm::end(); ?>

</div>
