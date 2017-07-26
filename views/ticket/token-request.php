<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

?>

<div class="token-request-view">
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-12">
                            <span>Please enter the token given on your exam sheet.</span>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin([
                        'enableClientValidation' => false,
                        'action' => Url::to([
                            'download2',
                            'step' => 2,
                        ]),
                        'method' => 'get'
                    ]); ?>

                    <?= $form->field(
                        $model,
                        'token',
                        ['inputOptions' => ['autofocus' => 'autofocus'],
                    ])->textInput([
                        'name' => 'token',
                        'class' => 'form-control',
                        'style' => 'text-align:center',
                        'placeholder' => 'Insert your token here!',
                    ])->label(false); ?><div class="help-block"></div>

                    <?php ActiveForm::end(); ?>
                </div>       
            </div>
        </div>
    </div>
</div>