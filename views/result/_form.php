<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */

$model->token = null;
?>

<?php $form = ActiveForm::begin([
    'enableClientValidation' => false,
    'action' => Url::to([
        'result/view'
    ]),
    'method' => 'get'
]); ?>

<div class="row">
    <div class="col-md-3"></div>
    <div class="col-md-6">
        <?= $form->field($model, 'token')->textInput([
            'name' => 'token',
            'class' => 'form-control input-lg',
            'placeholder' => 'Insert your token here!',
        ])->label(false); ?>
    </div>
    <div class="col-md-3"></div>
</div>

<?php ActiveForm::end(); ?>
