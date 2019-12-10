<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\User */

$this->title = \Yii::t('users', 'Reset password for user: {user}', [ 'user' => $model->username ]);
$this->params['breadcrumbs'][] = ['label' => \Yii::t('users', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = \Yii::t('users', 'Reset Password');
?>
<div class="user-reset-password">

    <h1><?= Html::encode($this->title) ?></h1>

	<?php if (Yii::$app->user->identity->change_password == 1) {
		echo '<div class="alert alert-warning" role="alert">' . \Yii::t('users', 'Please change your password.') . '</div>';
	} ?>

	<div class="user-form">

	    <?php $form = ActiveForm::begin(); ?>

	    <?= $form->field($model, 'password')->passwordInput(['maxlength' => true, 'value' => '']); ?>
	    <?= $form->field($model, 'password_repeat')->passwordInput(['maxlength' => true, 'value' => '']); ?>

	    <div class="form-group">
	        <?= Html::submitButton(\Yii::t('users', 'Reset'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>

	    <?php ActiveForm::end(); ?>

	</div>

</div>
