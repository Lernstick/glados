<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;
use app\assets\FormAsset;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $searchModel app\models\UserSearch */
/* @var $form yii\widgets\ActiveForm */

FormAsset::register($this);

?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'roles')->widget(Select2::classname(), [
                'data' => $searchModel->roleList,
                'pluginOptions' => [
                    'dropdownAutoWidth' => true,
                    'width' => 'auto',
                    'allowClear' => true,
                    'placeholder' => '',
                ],
                'options' => [
                    'multiple' => true,
                    'placeholder' => \Yii::t('users', 'Choose role(s) ...')
                ]
            ]); ?>
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
