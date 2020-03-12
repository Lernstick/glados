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
        title:  $hint.parent().find('label').html(),
        //title:  'Description',
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
    $(this).button('loading');
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

$('#copy-credentials2-button').on('click', function(e) {
    bindUsername = $("input[name^='Auth'][name$='[bindUsername]']");
    bindPassword = $("input[name^='Auth'][name$='[bindPassword]']");
    queryUsername = $("input[name^='Auth'][name$='[query_username]']");
    queryPassword = $("input[name^='Auth'][name$='[query_password]']");
    bindUsername.val(queryUsername.val());
    bindPassword.val(queryPassword.val());
});

JS;
$this->registerJs($js);

$js = <<< SCRIPT

method = $("select[name^='Auth'][name$='[method]']");

method_change = function(){
    var method = $("select[name^='Auth'][name$='[method]']");
    var bindScheme = $("input[name^='Auth'][name$='[bindScheme]']").closest("div.parent");
    var loginSearchFilter = $("input[name^='Auth'][name$='[loginSearchFilter]']").closest("div.parent");
    var loginAttribute = $("select[name^='Auth'][name$='[loginAttribute]']").closest("div.parent");
    var bindAttribute = $("select[name^='Auth'][name$='[bindAttribute]']").closest("div.parent");
    var bindUsername = $("input[name^='Auth'][name$='[bindUsername]']").closest("div.parent");
    var bindPassword = $("input[name^='Auth'][name$='[bindPassword]']").closest("div.parent");

    var selected = $(this).children("option:selected").val();
    if (selected == null) {
        selected = "{$model->method}";
    }
    if (selected == "bind_direct") {
        bindScheme.show();
        loginSearchFilter.show();
        loginAttribute.hide();
        bindAttribute.hide();
        bindUsername.hide();
        bindPassword.hide();
    } else if (selected == "bind_byuser") {
        bindScheme.hide();
        loginSearchFilter.hide();
        loginAttribute.show();
        bindAttribute.show();
        bindUsername.show();
        bindPassword.show();
    } else if (selected == "anonymous_bind") {
        bindScheme.hide();
        loginSearchFilter.hide();
        loginAttribute.show();
        bindAttribute.show();
        bindUsername.hide();
        bindPassword.hide();
    }
};

method_change();
method.change(method_change);

connection_method = $("select[name^='Auth'][name$='[connection_method]']");

connection_method_change = function(){
    var connection_method = $("select[name^='Auth'][name$='[connection_method]']");
    var ldap_uri = $("input[name^='Auth'][name$='[ldap_uri]']").closest("div.parent");
    var ldap_port = $("input[name^='Auth'][name$='[ldap_port]']").closest("div.parent");
    var ldap_scheme = $("select[name^='Auth'][name$='[ldap_scheme]']").closest("div.parent");

    var selected = $(this).children("option:selected").val();
    if (selected == null) {
        selected = "{$model->connection_method}";
    }
    console.log(ldap_uri);
    console.log(ldap_scheme);
    console.log(ldap_port);
    if (selected == "connect_via_uri") {
        ldap_uri.show();
        ldap_port.hide();
        ldap_scheme.hide();
    } else if (selected == "connect_via_domain") {
        ldap_uri.hide();
        ldap_port.show();
        ldap_scheme.show();
    }
};

connection_method_change();
connection_method.change(connection_method_change);

SCRIPT;
$this->registerJs($js);

if (!empty($model->getErrors('query_password'))) {
    $js = <<< JS
    // open the query modal
    $('#queryModal').modal('show');
JS;
    $this->registerJs($js);

} else if (!empty($model->success)) {
    Yii::$app->session->addFlash('success', $model->success . ' ' . \Yii::t('exams', 'You can now select them from the dropdown lists.'));
}

?>

<div class="alert alert-success" role="alert">
    <span class="glyphicon glyphicon-alert"></span>
    <span><?= \Yii::t('auth', 'For more information, please visit the {link}.', [
        'link' => Html::a('Manual / LDAP Authentication', ['/howto/view', 'id' => 'ldap-authentication.md'], ['class' => 'alert-link'])
    ]) ?></span>
</div>

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
            <div class="row">
                <div class="col-lg-4">
                    <?= Html::label($model->getAttributeLabel('mapping')); ?>
                    <div class="hint-block"><?= $model->getAttributeHint('mapping'); ?></div>
                </div>

                <div class="col-lg-8 <?= !empty($model->getErrors('query_password')) ? 'has-error' : ( empty($model->success) ? 'has-warning' : 'has-success' ) ?> ">
                    <div class="input-group pull-right">
                      <span></span>
                      <button class="btn btn-default input-group-addon" style="width:100%; border-radius: 4px;" type="button" data-toggle="modal" data-target="#queryModal"><?= !empty($model->getErrors('query_password')) ? '<i class="glyphicon glyphicon-remove"></i>&nbsp;' : ( empty($model->success) ? '' : '<i class="glyphicon glyphicon-ok"></i>&nbsp;' ) ?><?= \Yii::t('auth', 'Query for LDAP groups'); ?></button>
                      <span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-body">

            <div class="row">
                <div class="col-lg-12">
                    <div class="modal fade" id="queryModal" tabindex="-1" role="dialog" aria-labelledby="queryModalLabel">
                      <div class="modal-dialog" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="queryModalLabel">
                                <i class="glyphicon glyphicon-user"></i> <?= Html::label($model->getAttributeLabel('query_login')); ?>
                                <div class="hint-block"><?= $model->getAttributeHint('query_login'); ?></div>
                            </h4>
                          </div>
                          <div class="modal-body">

                            <div class="row">
                                <div class="col-lg-12">
                                    <?= $form->field($model, 'query_username', [
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

                                    <?= $form->field($model, 'query_password', [
                                        'template' => "{label}\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                                    ])->passwordInput() ?>
                                </div>
                            </div>

                            <hr>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="col-lg-12">
                                        <div class="help-block"><?= implode("<br>", $model->debug); ?></div>
                                        <div class="has-error"><div class="help-block"><?= $model->error; ?></div></div>
                                        <div class="has-success"><div class="help-block"><?= $model->success; ?></div></div>
                                    </div>
                                </div>
                            </div>

                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal"><?= \Yii::t('auth', 'Close') ?></button>
                            <?= Html::submitButton(\Yii::t('auth', 'Query'), ['class' => 'btn btn-primary', 'name' => 'query-groups-button', 'id' => 'query-groups-button']) ?>
                          </div>
                        </div>
                      </div>
                    </div>
                </div>
            </div>

            <?php
            foreach (array_keys($searchModel->roleList) as $key => $role) {
                ?><div class="row">
                    <div class="col-md-12 form-group <?= empty($model->groups) ? 'has-warning' : 'has-success' ?> ">
                        <?= Select2::widget([
                            'name' => $model->formName() . '[mapping][' . $role . ']',
                            'options' => [
                                'placeholder' => empty($model->groups) ? \Yii::t('auth', 'No groups found. Query for LDAP groups to fill this dropdown list.') : \Yii::t('auth', 'Choose LDAP Groups ...'),
                                'multiple' => true,
                            ],
                            'value' => array_keys($model->mapping, $role),
                            //'data' => array_combine(array_keys($model->mapping), array_keys($model->mapping)),
                            'data' => $model->groups,
                            'maintainOrder' => true,
                            'showToggleAll' => true,
                            'addon' => [
                                'prepend' => ['content' => \Yii::t('auth', 'LDAP Group(s)')],
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
                                    'noResults' => new JsExpression('function (params) { return "' . \Yii::t('auth', 'No groups found. Query for LDAP groups to fill this dropdown list.') . '"; }'),
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
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <div class="row">
                                <?= $form->field($model, 'connection_method', [
                                    'template' => "<div class='col-lg-4'>{label}</div>\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                                    'errorOptions' => ['class' => 'col-lg-8 help-block'],
                                    'options' => [
                                        'class' => '',
                                    ],
                                    'errorOptions' => ['tag' => false],
                                ])->dropdownList([
                                    $model::CONNECT_VIA_DOMAIN => \Yii::t('auth', 'Build the LDAP URI'),
                                    $model::CONNECT_VIA_URI => \Yii::t('auth', 'Connect via given LDAP URI'),
                                ]) ?>
                            </div>
                        </div>
                        <div class="panel-body">

                            <div id="connection_method-fields">

                                <div class="col-md-6 parent">
                                    <?= $form->field($model, 'ldap_scheme')->dropdownList([
                                    'ldap' => 'ldap',
                                    'ldaps' => 'ldaps',
                                ]) ?>
                                </div>

                                <div class="col-md-6 parent">
                                    <?= $form->field($model, 'ldap_port')->textInput(['maxlength' => true]) ?>
                                </div>

                                <div class="col-md-12 parent">
                                    <?= $form->field($model, 'ldap_uri')->textInput(['maxlength' => true]) ?>
                                </div>
                            </div>

                        </div>
                    </div>
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
                            <div class="row">
                                <?= $form->field($model, 'method', [
                                    'template' => "<div class='col-lg-4'>{label}</div>\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                                    'errorOptions' => ['class' => 'col-lg-8 help-block'],
                                    'options' => [
                                        'class' => '',
                                    ],
                                    'errorOptions' => ['tag' => false],
                                ])->dropdownList([
                                    $model::SCENARIO_BIND_DIRECT => \Yii::t('auth', 'Bind directly by login credentials'),
                                    $model::SCENARIO_BIND_BYUSER => \Yii::t('auth', 'Bind with given username and password'),
                                    $model::SCENARIO_ANONYMOUS_BIND => \Yii::t('auth', 'Bind with anonymous user'),
                                ]) ?>
                            </div>
                        </div>
                        <div class="panel-body">

                            <div id="method-fields">

                                <div class="col-md-12 parent">
                                    <?= $form->field($model, 'bindUsername', [
                                        'template' => "{label}\n<div class='col-lg-8'><div class='input-group'>{input}<span class='input-group-btn'>{button}</span></div></div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                                        'parts' => [
                                            'button' => Html::button('<span class="glyphicon glyphicon-copy" aria-hidden="true"></span>', [
                                                'class' => 'btn btn-default',
                                                'title' => \Yii::t('auth', 'Copy credentials from {field}', [
                                                    'field' => $model->getAttributeLabel('query_login'),
                                                ]),
                                                'name' => 'copy-credentials2-button',
                                                'id' => 'copy-credentials2-button'])
                                        ],
                                    ]) ?>

                                    <?= $form->field($model, 'bindPassword', [
                                        'template' => "{label}\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                                    ])->passwordInput() ?>
                                </div>

                                <div class="col-md-12 parent">
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
                                <div class="col-md-12 parent">
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

                                <div class="col-md-12 parent">
                                    <?= $form->field($model, 'bindScheme')->textInput(['maxlength' => true]) ?>
                                </div>
                                <div class="col-md-12 parent">
                                    <?= $form->field($model, 'loginSearchFilter')->textInput(['maxlength' => true]) ?>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?= $this->render('@app/views/_notification') ?>

    <?php Pjax::end(); ?>

    </div>

    <?php ActiveForm::end(); ?>

</div>
