<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\AuthTestForm */
/* @var $searchmodel app\models\AuthSearch */

$this->title = \Yii::t('auth', 'Test Authentication Method');

$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
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

	<?php $form = ActiveForm::begin(['id' => 'auth_test_form']); ?>

    <div class="row">
        <div class="col-lg-5">
            <div class="panel panel-info form-horizontal">
                <div class="panel-heading">
                    <i class="glyphicon glyphicon-user"></i> <?= Html::label($model->attributeLabels()['login']); ?>
                    <div class="hint-block"><?= $model->attributeHints()['login']; ?></div>
                </div>
                <div class="panel-body">

                    <?php echo $form->field($model, 'method', [
                        'template' => "{label}\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                    ])->dropDownList($searchModel->authSelectlist, [ 'prompt' => Yii::t('auth', 'Choose an authentication method ...') ]) ?>

                    <?= $form->field($model, 'username', [
                        'template' => "{label}\n<div class='col-lg-8'>{input}</div>\n<div class='col-lg-4'></div>{hint}\n{error}",
                        'labelOptions' => ['class' => 'col-lg-4 control-label'],
                        'errorOptions' => ['class' => 'col-lg-8 help-block'],
                    ]) ?>

                    <?= $form->field($model, 'password', [
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
            <div class="help-block"><?= implode("<br>", $model->authModel->debug); ?></div>
            <div class="has-error"><div class="help-block"><?= $model->authModel->error; ?></div></div>
            <div class="has-success"><div class="help-block"><?= $model->authModel->success; ?></div></div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
