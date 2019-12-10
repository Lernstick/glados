<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\AuthMigrateForm */
/* @var $searchmodel app\models\AuthSearch */

$this->title = \Yii::t('auth', 'Migrate users');
$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Migrate');
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Step 1');

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

        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'id' => 'migrate_form',
        ]); ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-lg-4">
                        <?= Html::label(\Yii::t('auth', 'Setup')); ?>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <?php echo $form->field($model, 'from')->dropDownList($searchModel->authSelectlist, [ 'prompt' => Yii::t('auth', 'Choose an authentication method ...') ]) ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo $form->field($model, 'to')->dropDownList($searchModel->authSelectlist, [ 'prompt' => Yii::t('auth', 'Choose an authentication method ...') ]) ?>

                    </div>
                </div>
            </div>
        </div>

        <div class="row">

        </div>
        <br>
        <div class="form-group">
            <?= Html::submitButton(\Yii::t('app', 'Next step'), ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
