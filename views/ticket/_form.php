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

    <?= $form->field($model, 'token')->textInput(['readOnly' => false]) ?>

    <?= $form->field($model, 'exam_id')->dropDownList($searchModel->getExamList(), [ 'prompt' => 'Choose an Exam ...' ]) ?>

    <?= $model->isNewRecord ? null : $form->field($model, 'start')->widget(DateTimePicker::classname(), [
	'options' => ['placeholder' => 'Enter start time ...'],
	'pluginOptions' => [
		'format' => 'yyyy-mm-dd hh:ii:ss',
		'todayHighlight' => true,
		'todayBtn' => true,
		'autoclose' => true,
	]
    ]); ?>

    <?= $model->isNewRecord ? null : $form->field($model, 'end')->widget(DateTimePicker::classname(), [
        'options' => ['placeholder' => 'Enter end time ...'],
        'pluginOptions' => [
                'format' => 'yyyy-mm-dd hh:ii:ss',
                'todayHighlight' => true,
		'todayBtn' => true,
                'autoclose' => true,
        ]
    ]); ?>

    <?= $form->field($model, 'test_taker')->textInput(); ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
