<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $searchModel app\models\TicketSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="col-md-6">
            <?php echo $form->field($model, 'role')->dropDownList($searchModel->roleList, [ 'prompt' => 'Choose a Role ...' ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?php echo $model->isNewRecord ? $form->field($model, 'password')->passwordInput(['maxlength' => true]) : null; ?>
        </div>
        <div class="col-md-6">        
            <?php echo $model->isNewRecord ? $form->field($model, 'password_repeat')->passwordInput(['maxlength' => true]) : null; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
	       <?= $form->field($model, 'change_password')->checkbox() ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? \Yii::t('users', 'Create') : \Yii::t('users', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
