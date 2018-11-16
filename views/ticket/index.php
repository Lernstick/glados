<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\widgets\ActiveField;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TicketSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $preSelect array */
/* @var $monitor bool */

$this->title = 'Tickets';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ticket-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php Pjax::begin(); ?>

    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [
            //['class' => 'kartik\grid\SerialColumn', 'order' => DynaGrid::ORDER_FIX_LEFT],

            [
                'order' => DynaGrid::ORDER_FIX_LEFT,
                'format' => 'raw',
                'label' => '#',
                'value' => function($model, $key, $index, $column) use ($monitor) {
                    $offset = $column->grid->dataProvider->getPagination()->getOffset();
                    return $monitor ? ActiveEventField::widget([
                        'options' => [ 'tag' => 'span' ],
                        'event' => 'ticket/' . $model->id,
                        'content' => $offset + $index + 1,
                        'jsonSelector' => 'state',
                        'jsHandler' => 'function(d, s){
                            t = s.parentNode.parentNode;
                            t.classList.remove("success");
                            t.classList.remove("info");
                            t.classList.remove("danger");
                            t.classList.remove("warning");
                            if(d == 0){
                                t.classList.add("success");
                            } else if(d == 1){
                                t.classList.add("info");
                            } else if(d == 2){
                                t.classList.add("danger");
                            } else if(d == 3){
                                t.classList.add("warning");
                            }
                        }',
                    ]) : $offset + $index + 1;
                },
            ],

            [
                'attribute' => 'createdAt',
                'format' => 'timeago',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'options' => ['placeholder' => 'Enter day...'],
                    'pluginOptions' => [
                       'format' => 'yyyy-mm-dd',
                       'todayHighlight' => true,
                       'autoclose' => true,
                    ]
                ],
                'visible' => false,
            ],
            [
                'attribute' => 'state',
                'format' => 'raw',
                'filter' => array(
                    0 => yii::$app->formatter->format(0, 'state'),
                    1 => yii::$app->formatter->format(1, 'state'),
                    2 => yii::$app->formatter->format(2, 'state'),
                    3 => yii::$app->formatter->format(3, 'state'),
                    4 => yii::$app->formatter->format(4, 'state'),
                ),
                'value' => function($model) use ($monitor) {
                    return $monitor ? ActiveEventField::widget([
                        'options' => [ 'tag' => 'span' ],
                        'content' => yii::$app->formatter->format($model->state, 'state'),
                        'event' => 'ticket/' . $model->id,
                        'jsonSelector' => 'state',
                        'jsHandler' => 'function(d, s){
                            if(d == 0){
                                s.innerHTML = "Open";
                            } else if(d == 1){
                                s.innerHTML = "Running";
                            } else if(d == 2){
                                s.innerHTML = "Closed";
                            } else if(d == 3){
                                s.innerHTML = "Submitted";
                            } else {
                                s.innerHTML = "Unknown";
                            }
                        }',
                    ]) : yii::$app->formatter->format($model->state, 'state');
                },
            ],
            [
                'attribute'=>'token',
                'filterType'=>GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'minimumInputLength' => 3,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['ticket/index', 'mode' => 'list', 'attr' => 'token']),
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
                ],
                'filterInputOptions' => [
                    'placeholder' => 'Any'
                ],
                'format'=>'raw'
            ],
            [
                'attribute'=>'examName',
                'filterType'=>GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'name']),
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
                ],
                'filterInputOptions' => [
                    'placeholder' => 'Any'
                ],
                'format'=>'raw'
            ],
            [
                'attribute' => 'examSubject',
                'filterType' => GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'subject']),
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
                ],
                'filterInputOptions' => [
                    'placeholder' => 'Any'
                ],
                'format' => 'raw'
            ],          
            //'start:timeago',
            //'end:timeago',
            [
                'attribute' => 'start',
                'format' => 'timeago',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'options' => ['placeholder' => 'Enter day...'],
                    'pluginOptions' => [
                       'format' => 'yyyy-mm-dd',
                       'todayHighlight' => true,
                       'autoclose' => true,
                    ]
                ],
            ],
            [
                'attribute' => 'end',
                'format' => 'timeago',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'options' => ['placeholder' => 'Enter day...'],
                    'pluginOptions' => [
                       'format' => 'yyyy-mm-dd',
                       'todayHighlight' => true,
                       'autoclose' => true,
                    ]
                ],
            ],            
            #'valid:boolean',
            [
                'attribute' => 'abandoned',
                'format' => 'boolean',
                'filter' => array(
                    'Yes' => 'Yes',
                    'No' => 'No',
                ),
            ],            
            [
                'attribute'=>'test_taker',
                'filterType'=>GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['ticket/index', 'mode' => 'list', 'attr' => 'testTaker']),
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
                ],
                'filterInputOptions' => [
                    'placeholder' => 'Any'
                ],
                'format'=>'raw'
            ],             
            [
                'attribute' => 'time_limit',
                'format' => 'raw',
                'visible' => false
            ],            
            [
                'attribute' => 'duration',
                'format' => 'duration',
                'visible' => false
            ],
            [
                'attribute' => 'valid',
                'format' => 'boolean',
                'visible' => false
            ],
            [
                'attribute' => 'ip',
                'format' => 'raw',
                'visible' => false
            ],
            /*[
                'attribute' => 'client_state',
                'format' => 'raw',
                'visible' => false,
                'value' => $monitor ? function($model) {
                    return ActiveEventField::widget([
                        'content' => yii::$app->formatter->format(StringHelper::truncate($model->client_state, 30), 'text'),
                        'event' => 'ticket/' . $model->id,
                        'jsonSelector' => 'client_state',
                        'jsHandler' => 'function(d, s){
                            if (d.length > 30) {
                                s.innerHTML = d.substr(0, 30) + "...";
                            }else{
                                s.innerHTML = d;
                            }
                        }',  
                    ]);
                } : 'client_state',
            ],*/

            [
                'attribute' => 'client_state',
                'format' => 'raw',
                'visible' => false,
                'value' =>  $monitor ? function($model) {
                    return ActiveEventField::widget([
                            'options' => [ 'tag' => 'span' ],
                            'content' => $model->client_state,
                            'event' => 'ticket/' . $model->id,
                            'jsonSelector' => 'client_state',
                        ]);
                } : 'client_state',
            ],

            [
                'attribute' => 'download_progress',
                'format' => 'raw',
                'visible' => false,
                'value' => function($model) use ($monitor) {
                    return $monitor ? ( '<div class="progress" style="display: inline-table; width:100%;">' . 
                        ActiveEventField::widget([
                            'content' => ActiveEventField::widget([
                                'options' => [ 'tag' => 'span' ],
                                'content' => yii::$app->formatter->format($model->download_progress, 'percent'),
                                'event' => 'ticket/' . $model->id,
                                'jsonSelector' => 'download_progress',
                                'jsHandler' => 'function(d, s){
                                    s.innerHTML = d;
                                    s.parentNode.style = "width:" + d;
                                }',
                            ]),
                            'event' => 'ticket/' . $model->id,
                            'jsonSelector' => 'download_lock',
                            'jsHandler' => 'function(d, s){
                                if(d == "1"){
                                    s.classList.add("active");
                                    s.parentNode.classList.add("in");
                                }else if(d == "0"){
                                    s.classList.remove("active");
                                    s.parentNode.classList.remove("in");
                                }
                            }',
                            'options' => [
                                'class' => 'progress-bar progress-bar-striped ' . ($model->download_lock == 1 ? 'active' : null),
                                'role' => '"progressbar',
                                'aria-valuenow' => '0',
                                'aria-valuemin' => '0',
                                'aria-valuemax' => '100',
                                'style' => 'width:' . yii::$app->formatter->format($model->download_progress, 'percent') . ';',
                            ]
                        ]) . 
                    '</div>' ) : yii::$app->formatter->format($model->download_progress, 'percent');
                },
            ],

            [
                'attribute' => 'backup_interval',
                'format' => 'raw',
                'value' =>  function($model) {
                    return $model->backup_interval == 0 ? 'No Backup' : yii::$app->formatter->format($model->backup_interval, 'duration');
                },
                'visible' => false
            ],
            [
                'attribute' => 'backup_size',
                'format' => 'shortSize',
                'visible' => false
            ],
            [
                'attribute' => 'backup_last',
                'format' => 'timeago',
                'visible' => false
            ],
            [
                'attribute' => 'backup_last_try',
                'format' => 'timeago',
                'visible' => false
            ],
            [
                'attribute' => 'backup_state',
                'format' => 'raw',
                'visible' => false,
                'value' => $monitor ? function($model) {
                    return ActiveEventField::widget([
                            'options' => [
                                'tag' => 'i',
                                'class' => 'glyphicon glyphicon-cog ' . ($model->backup_lock == 1 ? 'gly-spin' : 'hidden'),
                                'style' => 'float: left;',
                            ],
                            'event' => 'ticket/' . $model->id,
                            'jsonSelector' => 'backup_lock',
                            'jsHandler' => 'function(d, s){
                                if(d == "1"){
                                    s.classList.add("gly-spin");
                                    s.classList.remove("hidden");
                                }else if(d == "0"){
                                    s.classList.remove("gly-spin");
                                    s.classList.add("hidden");
                                }
                            }',
                        ]) . ActiveEventField::widget([
                            'options' => [
                                'style' => 'float:left'
                            ],
                            'content' => yii::$app->formatter->format($model->backup_state, 'ntext'),
                            'event' => 'ticket/' . $model->id,
                            'jsonSelector' => 'backup_state',
                        ]);
                } : 'backup_state',
            ],
            [
                'attribute' => 'restore_state',
                'format' => 'raw',
                'visible' => false
            ],
            [
                'attribute' => 'newestScreenshot',
                'format' => 'raw',
                'visible' => false,
                'value' => function($model) use ($monitor) {
                    return $monitor ? ActiveEventField::widget([
                        'options' => [
                            'tag' => 'img',
                            'src' => $model->newestScreenshot !== null ? $model->newestScreenshot->tsrc : '',
                            'alt' => $model->newestScreenshot !== null ? '' : 'No screenshot'
                        ],
                        'event' => 'ticket/' . $model->id,
                        'jsonSelector' => 'newestScreenshot',
                        'jsHandler' => 'function(d, s){
                            s.src = d;
                        }',
                    ]) : Html::img(
                        $model->newestScreenshot !== null ? $model->newestScreenshot->tsrc : '',
                        [ 'alt' => $model->newestScreenshot !== null ? '' : 'No screenshot' ]
                    );
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete} {report}',
                'buttons' => [
                    'report' => function ($url) {
                        return Html::a('<span class="glyphicon glyphicon-save-file"></span>', $url,
                            [
                                'title' => 'Generate PDF Report',
                                'data-pjax' => '0',
                            ]
                        );
                    },
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    if ($action === 'report') {
                        return Url::toRoute(['ticket/view', 'id' => $model->id, 'mode' => 'report']);
                    }
                    return Url::toRoute(['ticket/' . $action, 'id' => $model->id]);
                },
            ],
        ],
        'storage' => DynaGrid::TYPE_COOKIE,
        'theme' => 'simple-default',
        'gridOptions' => [
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'panel' => ['heading' => '<h3 class="panel-title">Your Tickets</h3>'],
            'rowOptions' => function($model) {
                return array_key_exists($model->state, $model->classMap) ? ['class' => $model->classMap[$model->state]] : null;
            },            
            'toolbar' =>  [
                ['content' => 
                    Html::a('<i class="glyphicon glyphicon-facetime-video"></i>', Url::current(['monitor' => $monitor ? false : true], true), ['data-pjax' => 0, 'class' => $monitor ? 'btn btn-danger pulse' : 'btn btn-default', 'title' => 'Monitor Tickets'])
                ],
                ['content' =>
                    Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'], ['data-pjax' => 0, 'class' => 'btn btn-success', 'title' => 'Create Ticket']) . ' ' .
                    Html::a('<i class="glyphicon glyphicon-envelope"></i>', ['update', 'mode' => 'submit'], ['data-pjax' => 0, 'class' => 'btn btn-info', 'title' => 'Submit Ticket']) . ' ' .
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['/ticket/index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => 'Reset Grid'])
                ],
                ['content' => '{dynagridFilter}{dynagridSort}{dynagrid}'],
                '{export}',
        ]            
        ],
        'options' => ['id' => 'dynagrid-ticket-index'] // a unique identifier is important
    ]); ?>

    <?= $this->render('@app/views/_notification', [
        'session' => $session,
    ]) ?>

    <?php Pjax::end(); ?>

</div>
