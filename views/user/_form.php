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

    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

    <?php echo $model->isNewRecord ? $form->field($model, 'password')->passwordInput(['maxlength' => true]) : null; ?>
    <?php echo $model->isNewRecord ? $form->field($model, 'password_repeat')->passwordInput(['maxlength' => true]) : null; ?>

    <?php echo $form->field($model, 'role')->dropDownList($searchModel->roleList, [ 'prompt' => 'Choose a Role ...' ]) ?>

	<?= $form->field($model, 'change_password')->checkbox() ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
