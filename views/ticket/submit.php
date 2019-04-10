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

$this->title = \Yii::t('tickets', 'Submit Ticket');
$this->params['breadcrumbs'][] = ['label' => 'Tickets', 'url' => ['index']];
$this->params['breadcrumbs'][] = \Yii::t('tickets', 'Submit');
?>
<div class="ticket-submit">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>

    <?php $form = ActiveForm::begin(['options' => ['data-pjax' => false], 'action' => Url::to([
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
        <?= Html::submitButton(\Yii::t('tickets', 'Submit'), ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <?php Pjax::end(); ?>


</div>
