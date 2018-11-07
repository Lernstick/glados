<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $form yii\widgets\ActiveForm */
/* @var $searchModel app\models\TicketSearch */

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

<div class="ticket-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'token')->textInput(['readOnly' => false]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'exam_id')->widget(Select2::classname(), [
                'data' => $searchModel->getExamList(),
                'options' => ['placeholder' => 'Choose an Exam ...'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $model->isNewRecord ? null : $form->field($model, 'start')->widget(DateTimePicker::classname(), [
                'options' => ['placeholder' => 'Enter start time ...'],
                'pluginOptions' => [
            	   'format' => 'yyyy-mm-dd hh:ii:ss',
            	   'todayHighlight' => true,
            	   'todayBtn' => true,
            	   'autoclose' => true,
                ]
            ]); ?>
        </div>

        <div class="col-md-6">
            <?= $model->isNewRecord ? null : $form->field($model, 'end')->widget(DateTimePicker::classname(), [
                'options' => ['placeholder' => 'Enter end time ...'],
                'pluginOptions' => [
                    'format' => 'yyyy-mm-dd hh:ii:ss',
                    'todayHighlight' => true,
                    'todayBtn' => true,
                    'autoclose' => true,
                ]
            ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'backup_interval', [
                'template' => '{label}<div class="input-group">{input}<span class="input-group-addon" id="basic-addon2">seconds</span></div>{hint}{error}'
            ])->textInput(['type' => 'number']); ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'time_limit', [
                'template' => '{label}<div class="input-group">{input}<span class="input-group-addon" id="basic-addon2">minutes</span></div>{hint}{error}'
            ])->textInput(['type' => 'number']); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'test_taker')->textInput(); ?>
        </div>
    </div>

    <?= YII_ENV_DEV ? $this->render('_form_dev', [
        'model' => $model,
        'form' => $form,
    ]) : null; ?>


    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Apply', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
