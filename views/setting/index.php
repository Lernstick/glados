<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SettingSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \Yii::t('setting', 'Settings');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="setting-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>
    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [
            [
                'attribute' => 'date',
                'format' => 'timeago',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'options' => ['placeholder' => \Yii::t('form', 'Enter day...')],
                    'pluginOptions' => [
                       'format' => 'yyyy-mm-dd',
                       'todayHighlight' => true,
                       'autoclose' => true,
                    ]
                ],
                'visible' => false,
            ],
            [
                'attribute' => 'type',
                'format' => 'raw',
                'visible' => false,
            ],
            [
                'attribute' => 'key',
                'format' => 'raw',
                'value' => function ($model) {
                    return \Yii::t('setting', $model->key);
                },
            ],
            'description',
            [
                'attribute' => 'value',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->value === null
                        ? $model->renderSetting($model->default_value, $model->type)
                        : $model->renderSetting($model->value, $model->type);
                },
            ],
            [
                'attribute' => 'default_value',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->renderSetting($model->default_value, $model->type);
                },
                'visible' => false,
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'order' => DynaGrid::ORDER_FIX_RIGHT,
                'contentOptions' => [
                    'class' => 'text-nowrap',
                    'style' => 'width:10px;'
                ],
                'template' => '{update}',
            ],
        ],
        'storage' => DynaGrid::TYPE_COOKIE,
        'theme' => 'simple-default',
        'gridOptions' => [
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'panel' => ['heading' => '<h3 class="panel-title">' . \Yii::t('setting', 'Settings') . '</h3>'],
            'toolbar' =>  [
                ['content' =>
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['/setting/index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => \Yii::t('setting', 'Reset Grid')])
                ],
                ['content' => '{dynagridFilter}{dynagridSort}{dynagrid}'],
                '{export}',
        ]            
        ],
        'options' => ['id' => 'dynagrid-setting-index'] // a unique identifier is important
    ]); ?>

    <?= $this->render('@app/views/_notification') ?>

    <?php Pjax::end(); ?>

</div>
