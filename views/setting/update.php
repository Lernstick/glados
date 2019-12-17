<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Setting */

$this->title = \Yii::t('setting', 'Edit Setting: {setting}', [
    'setting' => \Yii::t('setting', $model->key)
]);
$this->params['breadcrumbs'][] = ['label' => \Yii::t('setting', 'Settings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = \Yii::t('setting', 'Edit');

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

$js = <<< 'SCRIPT'
$("input[name='Setting[null]']").click(function(){
    if ($(this).is(':checked')) {
        $('#setting-value').attr("disabled", true);
    } else if ($(this).not(':checked')) {
        $('#setting-value').attr("disabled", false);
    }
});
SCRIPT;
$this->registerJs($js);

?>
<div class="exam-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="setting-form">

        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'value')->textArea(['maxlength' => true]) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
               <?= $form->field($model, 'null')->checkbox() ?>
            </div>
        </div>

        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? \Yii::t('setting', 'Create') : \Yii::t('setting', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
