<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\User */

$this->title = 'Reset password for user: ' . $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Reset Password';
?>
<div class="user-reset-password">

    <h1><?= Html::encode($this->title) ?></h1>

	<?php if (Yii::$app->user->identity->change_password == 1) {
		echo '<div class="alert alert-warning" role="alert">Please change your password.</div>';
	} ?>

	<div class="user-form">

	    <?php $form = ActiveForm::begin(); ?>

	    <?= $form->field($model, 'password')->passwordInput(['maxlength' => true, 'value' => '']); ?>
	    <?= $form->field($model, 'password_repeat')->passwordInput(['maxlength' => true, 'value' => '']); ?>

	    <div class="form-group">
	        <?= Html::submitButton('Reset', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>

	    <?php ActiveForm::end(); ?>

	</div>

</div>
