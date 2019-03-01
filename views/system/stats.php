<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Stats */

$this->title = 'System Statistics';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['stats']];

?>
<div class="config-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'rowOptions' => function($data) {
            return null;
        },

        'columns' => [
            'date:datetime',
            'key',
            [
                'attribute' => 'value',
                'format' => 'raw',
                /*'value' => function ($model) {
                    return yii::$app->formatter->format($model->value, $model->type);
                },*/
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{history}',
                'buttons' => [
                    'history' => function ($url) {
                        return Html::a('<span class="glyphicon glyphicon-stats"></span>', $url,
                            [
                                'title' => 'See history',
                                'data-pjax' => '0',
                            ]
                        );
                    },
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    if ($action === 'history') {
                        return Url::toRoute(['system/history', 'key' => $model->key]);
                    }
                    return Url::toRoute(['system/' . $action, 'id' => $model->id]);
                },
            ],
        ],
        'layout' => '{items} {pager}',
        'emptyText' => 'No statistics found.',
    ]); ?>

    <?php Pjax::end(); ?>

</div>