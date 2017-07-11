<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */

$this->title = 'Submit Results';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['result/submit']];
$this->params['breadcrumbs'][] = 'Done';

?>
<div class="result-create">

    <h1><?= Html::encode($this->title . ' - Done') ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'layout' => '{items} {summary} {pager}',
        'emptyText' => 'No results found in zip file. Please upload a zip file with the same directory structure as the result.zip file.',        
        'rowOptions' => function($model) {
            return ['class' => !empty($model->result) && file_exists($model->result) ? 'success' : 'danger'];
        },        
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'test_taker',
            'token',            
            'examName',
            [
                'attribute' => 'result',
                'value' => function ($model) {
                    return !empty($model->result) && file_exists($model->result) ? '<i class="glyphicon glyphicon-ok-sign"></i> Successfully submitted' : '<i class="glyphicon glyphicon-alert"></i> Error';
                },
                'format' => 'raw',
                'label' => 'Notice'
            ],            
        ],
    ]); ?>

</div>
