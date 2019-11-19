<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\AuthMigrateForm */
/* @var $searchmodel app\models\AuthSearch */

$from = $model->fromModel;
$to = $model->toModel;

$this->title = \Yii::t('auth', 'Migrate users from {from} to {to}', [
    'from' => is_object($from) ? $from->name : $from,
    'to' => $to->name,
]);

$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Migrate');
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Step 2');

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
<div class="auth-mirgate">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="migrate-form">

        <?php $form = ActiveForm::begin(['id' => 'migrate_form']); ?>

        <div class="row">
            <div class="col-md-6">
                <?php echo $form->field($model, 'from')->dropDownList($searchModel->authSelectlist, [
                    'readOnly' => true,
                    'disabled' => true,
                    'prompt' => Yii::t('auth', 'Choose an authentication method ...')
                ]) ?>
            </div>

            <div class="col-md-6">
                <?php echo $form->field($model, 'to')->dropDownList($searchModel->authSelectlist, [
                    'readOnly' => true,
                    'disabled' => true,
                    'prompt' => Yii::t('auth', 'Choose an authentication method ...')
                ]) ?>
            </div>
        </div>

        <?= $this->render($model->toModel->migrationForm, [
            'model' => $model,
            'searchModel' => $searchModel,
            'form' => $form,
        ]) ?>

        <?php
        //var_dump($model->users);
        //var_dump($searchModel->getUsernameList(['type' => $model->from]))
        ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-lg-4">
                        <?= Html::label($model->getAttributeLabel('users')); ?>
                        <div class="hint-block"><?= $model->getAttributeHint('users'); ?></div>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12 form-group <?= empty($model->users) ? 'has-warning' : 'has-success' ?> ">
                        <?= $form->field($model, 'users')->widget(Select2::classname(), [
                            'data' => $model->users,
                            'pluginOptions' => [
                                'tags' => true,
                                'allowClear' => true,
                                'language' => [
                                    'noResults' => new JsExpression('function (params) { return "' . \Yii::t('auth', 'No users found. Query for users to fill this dropdown list.') . '"; }'),
                                ],
                            ],
                            'options' => [
                                'placeholder' => empty($model->users) ? \Yii::t('auth', 'No users found. Query for users to fill this dropdown list.') : \Yii::t('auth', 'Choose Users to migrate ...'),
                                'multiple' => true,
                            ],
                            'maintainOrder' => true,
                            'showToggleAll' => true,
                            'addon' => [
                                'prepend' => ['content' => \Yii::t('auth', 'User(s)')],
                                'append' => ['content' => '<i class="glyphicon glyphicon-arrow-right"></i>'
                                    . \Yii::t('auth', '{users} will be migrated to the authentication method {method}', [
                                            'users' => '',
                                            'method' => ''
                                        ])],
                                'contentAfter' => '<span class="input-group-addon" style="background-color:white; width:100px;">' . $to->name . '</span>',
                            ],
                        ])->label(false)->hint(false); ?>
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="form-group">
            <?= Html::submitButton(\Yii::t('auth', 'Migrate'), ['class' => 'btn btn-primary', 'id' => 'submit-button', 'name' => 'submit-button']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
