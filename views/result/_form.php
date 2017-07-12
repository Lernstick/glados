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
        <?= $form->field(
            $model,
            'token',
            ['inputOptions' => ['autofocus' => 'autofocus']
        ])->textInput([
            'name' => 'token',
            'class' => 'form-control input-lg',
            'style' => 'text-align:center',
            'placeholder' => 'Insert your token here!',
        ])->label(false); ?>
    </div>
    <div class="col-md-3"></div>
</div>

<?php ActiveForm::end(); ?>
