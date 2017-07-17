<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */

$this->title = 'Submit Results';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['result/submit']];
$this->params['breadcrumbs'][] = 'Summary';
$this->title .= ' - Summary';

?>
<div class="result-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="media-body">
        <span>The results are submitted! The list further down gives an overview of the submitted results.</span>
    </div>
    <hr>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'layout' => '{items} {summary} {pager}',
        'emptyText' => 'No results found in zip file. Please upload a zip file with the same directory structure as the result.zip file.',        
        'rowOptions' => function($model) {
            return ['class' => !empty($model->result) && file_exists($model->result) ? 'success' : 'danger'];
        },
        'rowOptions' => function($model) {
            return ['class' => !empty($model->result) && file_exists($model->result) ? 'alert alert-success success' : 'alert alert-danger danger'];
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'test_taker',
            'token',            
            'examName',
            [
                'attribute' => 'result',
                'value' => function ($model) {
                    return !empty($model->result) && file_exists($model->result) ? '<i class="glyphicon glyphicon-ok-sign"></i> Successfully submitted.' : '<i class="glyphicon glyphicon-alert"></i> Error submitting result. Please check your zip file.';
                },
                'format' => 'raw',
                'label' => 'Notice'
            ],            
        ],
    ]); ?>

</div>
