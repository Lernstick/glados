<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;
use app\assets\FormAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Role */
/* @var $form yii\widgets\ActiveForm */

FormAsset::register($this);

?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'readonly' => true]) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?= $form->field($model, 'children')->widget(Select2::classname(), [
                'data' => $model->childrenFormList,
                'pluginOptions' => [
                    'dropdownAutoWidth' => true,
                    'width' => 'auto',
                    'allowClear' => true,
                    'placeholder' => '',
                    'ajax' => [
                        'url' => \yii\helpers\Url::to(['role/index', 'mode' => 'list', 'attr' => 'name']),
                        'dataType' => 'json',
                        'delay' => 250,
                        'cache' => true,
                        'data' => new JsExpression('function(params) {
                            return {
                                q: params.term,
                                page: params.page,
                                per_page: 10
                            };
                        }'),
                        'processResults' => new JsExpression('function(data, page) {
                            return {
                                results: data.results,
                                pagination: {
                                    more: data.results.length === 10 // If there are 10 matches, theres at least another page
                                }
                            };
                        }'),
                    ],
                    'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                    'templateResult' => new JsExpression('function(q) { return q.text; }'),
                    'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                ],
                'options' => [
                    'value' => array_keys($model->childrenFormList),
                    'multiple' => true,
                    'placeholder' => \Yii::t('user', 'Choose permission(s) for this role ...')
                ]
            ]); ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? \Yii::t('users', 'Create') : \Yii::t('user', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
