<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model Result */
/* @var $dataProvider ArrayDataProvider */

$this->title = \Yii::t('results', 'Submit Results');
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['result/submit']];
$this->params['breadcrumbs'][] = \Yii::t('results', 'Step 2');
$this->title .= ' - ' . \Yii::t('results', 'Step 2');

?>
<div class="result-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-alert"></span>
        <span><?= \Yii::t('results', 'For more information, please visit {link}.', [
            'link' => Html::a('Manual / Submit results back to the student', ['/howto/view', 'id' => 'submit-results.md'], ['class' => 'alert-link', 'target' => '_new'])
        ]) ?></span>
    </div>

    <div class="well">
        <span><?= \Yii::t('results', 'The list further down contains all results found in the uploaded Results ZIP-file. Please check if all results are present. In this list you see whether the ticket has already a result associated to it or not. Please notice, that when proceeding with the button further down, already existing results will be <b>overwritten permanently</b>. If you want to remove results from being processed, please edit the ZIP-file and reupload the file in Step 1.') ?></span>
    </div>

    <?php Pjax::begin(); ?>

        <?= $this->render('/result/zip_contents', [
            'step' => 2,
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]) ?>

    <?php Pjax::end(); ?>

    <?php $form = ActiveForm::begin([
        'action' => ['result/submit', 'mode' => 'step3', 'hash' => $model->hash],
    ]); ?>

    <hr>
    <div class="form-group">
        <?= Html::a('<i class="glyphicon glyphicon-chevron-left"></i>' . \Yii::t('app', 'Back'), ['result/submit'], [
            'class' => 'btn btn-default',
        ]); ?>
        <?= Html::submitButton(\Yii::t('results', 'Submit all results') . '<i class="glyphicon glyphicon-chevron-right"></i>', ['class' => 'btn btn-success pull-right']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
