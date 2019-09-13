<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Auth */

$this->title = \Yii::t('auth', 'Test Authentication Method Nr. {id} of type {type}', [
	'id' => $model->id,
	'type' => $model->obj->typeName,
]);

$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Test');

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
<div class="auth-test">

    <h1><?= Html::encode($this->title) ?></h1>

	<?php $form = ActiveForm::begin(['id' => 'ad_form']); ?>

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
                            <?= Html::submitButton(\Yii::t('auth', 'Test Login'), ['class' => 'btn btn-primary', 'name' => 'test-auth-button', 'id' => 'test-auth-button']) ?>
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

    <?php ActiveForm::end(); ?>

</div>
