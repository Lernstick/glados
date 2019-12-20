<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

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
        $('#setting-value').trigger('input');
    }
});

$("#setting-value").on('input', function() {
    $.pjax.reload({
        container: "#preview",
        fragment: "body",
        type: 'POST',
        data: {
            'preview[value]': $("#setting-value").val(),
            'preview[key]': $("#setting-key").val()
        },
        async:false
    });
});

SCRIPT;
$this->registerJs($js);

?>
<div class="exam-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="setting-form">

        
        <div class="row">
            <div class="col-md-12">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="col-md-12">
                    <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($model, 'key')->hiddenInput()->label(false)->hint(false); ?>
                    <?= $form->field($model, 'default_value')->hiddenInput()->label(false)->hint(false); ?>

                    <?= call_user_func(array($form->field($model, 'value'), $model->typeMapping()[$model->type][0]), $model->typeMapping()[$model->type][1])->label(\Yii::t('setting', $model->key))->hint($model->description); ?>
                    <?= $form->field($model, 'null')->checkbox() ?>
                </div>

                <div class="col-md-12">
                    <?= Html::label($model->getAttributeLabel('default_value')); ?>
                    <div class="hint-block"><?= $model->getAttributeHint('default_value'); ?></div>
                    <div>
                        <?= $model->renderSetting($model->default_value, $model->type); ?>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <?= Html::submitButton($model->isNewRecord ? \Yii::t('setting', 'Create') : \Yii::t('setting', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
            <div class="col-md-6" style="overflow:hidden;border:1px solid black;height:500px;">
                <?php Pjax::begin([
                    'id' => 'preview'
                ]); ?>
                    <object type="text/html">
                        <?= $contents ?>
                    </object>
                <?php Pjax::end(); ?>
            </div>

        </div>



    </div>

</div>
