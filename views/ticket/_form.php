<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datetime\DateTimePicker;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $form yii\widgets\ActiveForm */
/* @var $searchModel app\models\TicketSearch */

?>

<div class="ticket-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'token')->textInput(['readOnly' => false]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'exam_id')->dropDownList($searchModel->getExamList(), [ 'prompt' => 'Choose an Exam ...' ]) ?>
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
            ])->textInput(['type' => 'number'])
            ->hint('Set "0" to disable automatic backup'); ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'time_limit', [
                'template' => '{label}<div class="input-group">{input}<span class="input-group-addon" id="basic-addon2">minutes</span></div>{hint}{error}'
            ])->textInput(['type' => 'number'])
            ->hint('Leave empty to inherit the value configured in the exam' . (isset($model->exam) ? ' (' . yii::$app->formatter->format($model->exam->time_limit, 'timeLimit') . ')' : '') . '. Set to "0" for no time limit. Notice, this will override the setting in the exam.'); ?>
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
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
