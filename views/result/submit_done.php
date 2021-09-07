<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider ActiveDataProvider */
/* @var $model Result */

$this->title = \Yii::t('results', 'Submit Results');
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['result/submit']];
$this->params['breadcrumbs'][] = \Yii::t('results', 'Summary');
$this->title .= ' - ' . \Yii::t('results', 'Summary');

?>
<div class="result-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-info" role="alert">
        <span class="glyphicon glyphicon-alert"></span>
        <span><?= \Yii::t('results', 'Please visit {link} to learn how the student can receive his/her exam result.', [
            'link' => Html::a('Manual / Get the exam result as a student', ['/howto/view', 'id' => 'get-exam-result.md'], ['class' => 'alert-link', 'target' => '_new'])
        ]) ?></span>
    </div>

    <div class="well">
        <span><?= \Yii::t('results', 'The results are submitted! The list further down gives an overview of the submitted results.') ?></span>
    </div>

    <?php Pjax::begin(); ?>

        <?= $this->render('/result/zip_contents', [
            'step' => 'done',
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]) ?>

    <?php Pjax::end(); ?>

    <hr>
    <div class="form-group">
        <?= Html::a('<i class="glyphicon glyphicon-chevron-left"></i>' . \Yii::t('app', 'Back'), [
            'result/submit',
            'mode' => 'step2',
            'hash' => $model->hash,
        ], [
            'class' => 'btn btn-default',
        ]); ?>
        <?= Html::a(\Yii::t('app', 'Finish') . '<i class="glyphicon glyphicon-chevron-right"></i>', [
            'site/index',
        ], [
            'class' => 'btn btn-success pull-right',
        ]); ?>
    </div>

</div>
