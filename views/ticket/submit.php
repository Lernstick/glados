<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\helpers\Url;
use yii\widgets\ActiveForm;


/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

$script = <<< JS
$("form input:text, form textarea").first().focus();
JS;
//$this->registerJs($script);

$this->title = 'Submit Ticket';
$this->params['breadcrumbs'][] = ['label' => 'Tickets', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Submit';
?>
<div class="ticket-submit">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>

    <?php $form = ActiveForm::begin(['options' => ['data-pjax' => true], 'action' => Url::to([
        'update',
        'mode' => 'submit',
    ])]); ?>

    <?php
    if($model->state == $model::STATE_CLOSED) {
        echo $form->field($model, 'token')->textInput(['readOnly' => true]);
    } else {
        echo $form->field($model, 'token')->textInput([
            'readOnly' => false,
            'autofocus' => 'autofocus',
            'class' => 'form-control',
            'tabindex' => '1',
            ]);
        //echo Html::label('Token');
        //echo Html::input('text', 'token', Yii::$app->request->get('token'), ['autofocus' => 'autofocus', 'class' => 'form-control', 'tabindex' => '1']);
        ?><div class="help-block"></div><?php
    }
    ?>

    <?= $model->state == $model::STATE_CLOSED ? $form->field($model, 'test_taker', ['inputOptions' => ['autofocus' => 'autofocus', 'class' => 'form-control', 'tabindex' => '1']])->textInput() : null  ?>

    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <?php Pjax::end(); ?>


</div>
