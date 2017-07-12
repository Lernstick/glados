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

?>
<div class="result-create">

    <h1><?= Html::encode($this->title . ' - Step 2') ?></h1>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'layout' => '{items} {summary} {pager}',
        'emptyText' => 'No results found in zip file. Please upload a zip file with the same directory structure as the result.zip file.',
        'rowOptions' => function($model) {
            return ['class' => !empty($model->result) && file_exists($model->result) ? 'warning' : 'success'];
        },        
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'test_taker',
            'token',            
            'examName',
            [
                'attribute' => 'result',
                'value' => function ($model) {
                    return !empty($model->result) && file_exists($model->result) ? '<i class="glyphicon glyphicon-alert"></i> There is already a submitted result. The existing one will be overwritten!' : '';
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