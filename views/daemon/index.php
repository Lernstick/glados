<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $searchModel app\models\DaemonSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $runningDaemons integer */
/* @var $minDaemons Setting */
/* @var $maxDaemons Setting */

$this->title = \Yii::t('daemons', 'Daemons');
$this->params['breadcrumbs'][] = $this->title;

echo ActiveEventField::widget([
    'event' => 'runningDaemons',
    'jsonSelector' => 'runningDaemons',
    'jsHandler' => 'function(d, s) {
        $("#reload").click();
    }',
])

?>
<div class="daemon-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin([
        'id' => 'action-container',
        'linkSelector' => '.action',
        'enablePushState' => false,
    ]); ?>
    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'daemon-list'
    ]);

        /**
         * A dummy event in the group "daemon", such that if no event in the group "daemon" is active
         * the new js event handlers won't be registered. Also they won't be unregistered if the last
         * element is disappearing from the view. Using this, an event of type "daemon" will always be 
         * present in the view.
         */
        echo ActiveEventField::widget([
            'event' => 'daemon:daemon/ALL',
            'jsonSelector' => 'dummy',
            'jsHandler' => 'function(d, s){}', // do nothing
        ]); ?>

        <div id="helper" class="hidden">
            <!-- Don't remove this button! Make it invisible instead. -->
            <a class="btn btn-default" id="reload" href=""><i class="glyphicon glyphicon-refresh"></i>&nbsp;<?= Yii::t('app', 'Reload') ?></a>
        </div>

        <div class="pre-monitor row">
            <div class="col-md-6">
                <div class="bs-callout bs-callout-info bs-callout-hover">
                    <h4><?= substitute('{name}: {value}', [
                        'name' => $minDaemons->name,
                        'value' => $minDaemons->get('minDaemons'),
                    ]); ?></h4>
                    <p><?= $minDaemons->description ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bs-callout bs-callout-info bs-callout-hover">
                    <h4><?= substitute('{name}: {value}', [
                        'name' => $maxDaemons->name,
                        'value' => $maxDaemons->get('maxDaemons'),
                    ]); ?></h4>
                    <p><?= $maxDaemons->description ?></p>
                </div>
            </div>
        </div>

        <div class="exam-monitor">

            <?= GridView::widget( [
                'dataProvider' => $dataProvider,
                'layout' => '{pager} {summary} {items}',
                'emptyText' => $this->render('_nav'),
                'showOnEmpty' => false,
                'columns' => [
                    [
                        'attribute' => 'pid',
                        'headerOptions' => ['class' => 'fit'],
                    ],
                    [
                        'attribute' => 'description',
                        'headerOptions' => ['class' => 'col-md-1'],
                        'format' => 'text'
                    ],
                    [
                        'attribute' => 'load',
                        'headerOptions' => ['class' => 'fit'],
                        /**
                         * @param $model app\models\Daemon the current data model being rendered
                         * @param $key integer the key value associated with the current data model
                         * @param $index integer the zero-based index of the data model in the model array returned by $dataProvider
                         * @param $grid yii\grid\DataColumn the GridView object
                         */
                        'value' => function($model, $key, $index, $grid){
                            return ActiveEventField::widget([
                                'options' => ['tag' => 'span'],
                                'id' => 'wdl' . $model->id,
                                'content' => yii::$app->formatter->format($model->load, 'percent'),
                                'event' => 'daemon:daemon/' . $model->id,
                                'jsonSelector' => 'load',
                            ]);
                        },
                        'format' => 'raw',
                    ],
                    [
                        'attribute' => 'memory',
                        'headerOptions' => ['class' => 'col-md-1'],
                        /**
                         * @param $model app\models\Daemon the current data model being rendered
                         * @param $key integer the key value associated with the current data model
                         * @param $index integer the zero-based index of the data model in the model array returned by $dataProvider
                         * @param $grid yii\grid\DataColumn the GridView object
                         */
                        'value' => function($model, $key, $index, $grid){
                            return ActiveEventField::widget([
                                'options' => ['tag' => 'span'],
                                'id' => 'wdm' . $model->id,
                                'content' => yii::$app->formatter->format($model->memory, ['shortSize', 'decimals' => 1]),
                                'event' => 'daemon:daemon/' . $model->id,
                                'jsonSelector' => 'memory',
                            ]);
                        },
                        'format' => 'raw',
                    ],
                    [
                        'attribute' => 'state',
                        /**
                         * @param $model app\models\Daemon the current data model being rendered
                         * @param $key integer the key value associated with the current data model
                         * @param $index integer the zero-based index of the data model in the model array returned by $dataProvider
                         * @param $grid yii\grid\DataColumn the GridView object
                         */
                        'value' => function($model, $key, $index, $grid){
                            return ActiveEventField::widget([
                                'options' => ['tag' => 'span'],
                                'id' => 'wds' . $model->id,
                                'content' => yii::$app->formatter->format($model->state, 'text'),
                                'event' => 'daemon:daemon/' . $model->id,
                                'jsonSelector' => 'state',
                            ]);
                        },
                        'format' => 'raw',
                    ],
                    [
                        'attribute' => 'started_at',
                        'format' => 'timeago',
                        'headerOptions' => ['class' => 'col-md-2'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'contentOptions' => [
                            'class' => 'text-nowrap',
                            'style' => 'width:10px;',
                        ],
                        'template' => '{view} {stop} {kill}',
                        'buttons' => [
                            'stop' => function ($url) {
                                return Html::a('<span class="glyphicon glyphicon-off"></span>', $url, [
                                    'title' => \Yii::t('daemons', 'Stop'),
                                    'class' => 'action',
                                ]);
                            },
                            'kill' => function ($url) {
                                return Html::a('<span class="text-danger glyphicon glyphicon-fire"></span>', $url, [
                                    'title' => \Yii::t('daemons', 'Kill'),
                                    'class' => 'action',
                                    'data' => [
                                        'confirm' => \Yii::t('daemons', 'Are you sure you want to kill this process?'),
                                    ],
                                ]);
                            },
                        ],
                    ],
                ],
                'pager' => [
                    'layout' => '<div class="row"><div class="col-md-12 col-xs-12">{actions}&nbsp;{LinkPager} {CustomPager}</div></div>',
                    'elements' => [
                        'actions' => $this->render('_nav')
                    ],
                    'class' => app\widgets\CustomPager::className(),
                    'selectedLayout' => Yii::t('app', '{selected} <span style="color: #737373;">items</span>'),
                ],
            ] ); ?>

        </div>

    <?php Pjax::end(); ?>

</div>
