<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */

$this->title = 'Submit Results';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['result/submit']];
$this->params['breadcrumbs'][] = 'Step 2';
$this->title .= ' - Step 2';

?>
<div class="result-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="media-body">
        <span>The list further down contains all results found in the uploaded Results ZIP-file. Please check if all results are present. In this list you see whether the ticket has already a result associated to it or not. Please notice, that when proceeding with the button further down, already existing results will be <b>overwritten permanently</b>. If you want to remove results from being processed, please edit the ZIP-file and reupload the file in Step 1.</span>
    </div>
    <hr>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'layout' => '{items} {summary} {pager}',
        'rowOptions' => function($model) {
            return ['class' => !empty($model->result) && file_exists($model->result) ? 'alert alert-warning warning' : 'alert alert-success success'];
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'test_taker',
            'token',
            'examName',
            [
                'attribute' => 'result',
                'value' => function ($model) {
                    return !empty($model->result) && file_exists($model->result) ? '<i class="glyphicon glyphicon-alert"></i> There is already a submitted result. The existing one will be overwritten!' : '<i class="glyphicon glyphicon-ok"></i> This Ticket has no submitted result yet.';
                },
                'format' => 'raw',
                'label' => 'Notice'
            ],            
        ],
    ]); ?>
    <?php Pjax::end(); ?>

    <?php $form = ActiveForm::begin([
        'action' => ['result/submit', 'mode' => 'step3', 'hash' => $model->hash],
    ]); ?>

    <div class="form-group">
        <?= Html::submitButton('Submit all results', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
