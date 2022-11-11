<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;
use app\assets\FormAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Role */
/* @var $form yii\widgets\ActiveForm */
/* @var $dataProvider ArrayDataProvider */

FormAsset::register($this);

?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'readonly' => !$model->isNewRecord]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?= Html::beginTag('div', [
                'class' => substitute('form-group field-role-children {class}', [
                    'class' => $model->hasErrors('children') ? 'has-error' : '',
                ]),
            ]); ?>
                <?= Html::activeLabel($model, 'children', ['class' => 'control-label']); ?>
                <?= Html::activeHint($model, 'children', ['class' => 'hint-block']); ?>
                <?= Html::error($model, 'children', ['class' => 'help-block']); ?>
            <?= Html::endTag('div'); ?>
        </div>
        <div class="col-md-12">
            <?= $this->render('_permissions', [
                'form' => $form,
                'dataProvider' => $dataProvider,
                'permissions' => $model->children,
            ]) ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? \Yii::t('users', 'Create') : \Yii::t('user', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
