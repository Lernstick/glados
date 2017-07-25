<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

$this->title = 'Exam Token';
$this->params['breadcrumbs'] = [
    $this->title
];

?>
<div class="download-view">

    <div class="col-md-12">
        <p>Please enter the token given on your exam sheet.</p>

        <?php $form = ActiveForm::begin([
            'enableClientValidation' => false,
            'action' => Url::to([
                'download2',
                'step' => 2,
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
                    'class' => 'form-control',
                    'style' => 'text-align:center',
                    'placeholder' => 'Insert your token here!',
                ])->label(false); ?><div class="help-block"></div>
            </div>
            <div class="col-md-3"></div>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
