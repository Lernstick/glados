<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datetime\DateTimePicker;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $form yii\widgets\ActiveForm */
/* @var $searchModel app\models\TicketSearch */

?>
<hr>
<div class="row dev_item">
    <div class="col-md-6">
    	<?= $form->field($model, 'ip')->textInput(); ?>
        <?= $form->field($model, 'download_lock')->checkBox(); ?>
        <?= $form->field($model, 'backup_lock')->checkbox() ?>
        <?= $form->field($model, 'restore_lock')->checkbox() ?>
        <?= $form->field($model, 'bootup_lock')->checkbox() ?>
        <?= $form->field($model, 'last_backup')->checkbox() ?>
    </div>
</div>
