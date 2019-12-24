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
        $('#setting-value').val($('#setting-default_value').val());
    }
});

$("#setting-value").on('change keyup paste', function(){
    if ($(this).val() == $("input[name='Setting[default_value]']").val()) {
        $("input[name='Setting[null]']").prop( "checked", true );
    } else {
        $("input[name='Setting[null]']").prop( "checked", false );        
    }
});

function reload() {
    $('#setting-form').data('yiiActiveForm').submitting = false;
    $('#setting-form').yiiActiveForm('validate');
    if ($("#setting-form").find(".has-error").length == 0) {
        $.pjax.reload({
            container: "#preview",
            fragment: "body",
            type: 'POST',
            data: {
                'preview[value]': $("#setting-value").val(),
                'preview[key]': $("#setting-key").val(),
                '_csrf': $("input[name='_csrf']").val()
            },
            async:true
        });
    }
}

$("#preview-button").click(function() {
    reload();
});

$("#preview").on('pjax:send', function() {
  $('#preview-div').toggleClass('loading')
})
$("#preview").on('pjax:complete', function() {
  $('#preview-div').toggleClass('loading')
})

SCRIPT;
$this->registerJs($js);

?>
<div class="exam-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="setting-form">

        <div class="row">
            <div class="col-md-6">
                <?php $form = ActiveForm::begin([
                    'id' => 'setting-form',
                    'enableClientValidation' => true,
                ]); ?>
                <div class="col-md-12">
                    <?= $form->field($model, 'key')->hiddenInput()->label(false)->hint(false); ?>
                    <?= $form->field($model, 'default_value')->hiddenInput()->label(false)->hint(false); ?>

                    <?= call_user_func(array($form->field($model, 'value'), $model->typeMapping()[$model->type][0]), $model->typeMapping()[$model->type][1])->label(\Yii::t('setting', $model->name))->hint($model->description); ?>

                    <?= $form->field($model, 'null')->checkbox() ?>
                </div>

                <div class="form-group col-md-12">
                    <?= Html::label($model->getAttributeLabel('default_value')); ?>
                    <div class="hint-block"><?= $model->getAttributeHint('default_value'); ?></div>
                    <div>
                        <?= $model->renderSetting($model->default_value, $model->type); ?>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <?= Html::submitButton($model->isNewRecord ? \Yii::t('setting', 'Create') : \Yii::t('setting', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                    <?= Html::Button(\Yii::t('setting', 'Reload Preview'), [
                        'id' => 'preview-button',
                        'class' => 'btn btn-primary'
                    ]) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
            <div class="col-md-6">
                <div class="col-md-12">
                    <?= Html::label(Yii::t('setting', 'Preview')); ?>
                    <div id="preview-div" class="preview form-control">
                        <?= $contents ?>
                    </div>
                </div>
            </div>
        </div>



    </div>

</div>
