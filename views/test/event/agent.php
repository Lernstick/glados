<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $event app\models\forms\EventItemSend */

$this->title = 'Send agent events';
$this->params['breadcrumbs'][] = $this->title;

if ($event->isNewRecord) {
    $event->event = 'agent/1234';
    $event->priority = 0;
    $event->data = 'this is event number $i';
    $event->nrOfTimes = 1;
}

?>
<div class="event-form">
    <div class="row">
        <div class="col-md-6">
            <?php $form = ActiveForm::begin(); ?>
            <?= $form->field($event, 'event')->textInput() ?>
            <?= $form->field($event, 'priority')->textInput() ?>
            <?= $form->field($event, 'data')->textInput() ?>
            <?= $form->field($event, 'nrOfTimes')->textInput() ?>
            <div class="form-group">
                <?= Html::submitButton('Generate', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
