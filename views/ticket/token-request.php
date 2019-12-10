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
                        <div class="col-xs-8">
                            <span><?= \Yii::t('client', 'Please enter the token given on your exam sheet.') ?></span>
                        </div>
                        <div class="col-xs-4">
                            <div class="pull-right">
                                <?= Html::a(
                                    '<span class="glyphicon glyphicon-question-sign"></span>',
                                    ['howto/view', 'id' => 'token-request-help.md', 'mode' => 'inline'],
                                    ['onclick' => 'window.open("' . Url::to(['howto/view', 'id' => 'token-request-help.md', 'mode' => 'inline']) . '", "Help", "titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=800"); return false;']
                                ) ?>
                            </div>
                        </div>                        
                    </div>
                </div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin([
                        'enableClientValidation' => false,
                        'action' => Url::to([
                            'download',
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
                        'placeholder' => \Yii::t('client', 'Insert your token here!'),
                    ])->label(false)->hint(false); ?><div class="help-block"></div>

                    <?php ActiveForm::end(); ?>
                </div>       
            </div>
        </div>
    </div>
</div>