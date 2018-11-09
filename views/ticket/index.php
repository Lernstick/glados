<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveField;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;
use yii\bootstrap\Dropdown;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TicketSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $preSelect array */

$this->title = 'Tickets';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ticket-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php Pjax::begin(); ?>

    <?=Html::beginForm(['ticket/bulk'], 'post', ['id' => 'ticket-index-form']);?>

    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [
            ['class' => 'kartik\grid\SerialColumn', 'order' => DynaGrid::ORDER_FIX_LEFT],

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
                'format' => 'state',
                'filter' => array(
                    0 => yii::$app->formatter->format(0, 'state'),
                    1 => yii::$app->formatter->format(1, 'state'),
                    2 => yii::$app->formatter->format(2, 'state'),
                    3 => yii::$app->formatter->format(3, 'state'),
                    4 => yii::$app->formatter->format(4, 'state'),
                ),
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
            /*[
                'attribute' => 'examSubject',
                'filter' => $searchModel->subjectList,
            ],*/
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
            [
                'attribute' => 'client_state',
                'format' => 'raw',
                'visible' => false
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
                'visible' => false
            ],
            [
                'attribute' => 'restore_state',
                'format' => 'raw',
                'visible' => false
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
            ['class'=>'kartik\grid\CheckboxColumn', 'order'=>DynaGrid::ORDER_FIX_RIGHT],
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
                    Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'], ['data-pjax' => 0, 'class' => 'btn btn-success', 'title' => 'Create Ticket']) . ' ' .
                    Html::a('<i class="glyphicon glyphicon-envelope"></i>', ['update', 'mode' => 'submit'], ['data-pjax' => 0, 'class' => 'btn btn-info', 'title' => 'Submit Ticket']) . ' ' .
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['/ticket/index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => 'Reset Grid'])
                ],
                ['content' => '{dynagridFilter}{dynagridSort}{dynagrid}'],
                '{export}',
                ['content' =>
                    '<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <i class="glyphicon glyphicon-list-alt"></i>
                        Actions&nbsp;<span class="caret"></span>
                    </button>
                    ' . Dropdown::widget([
                        'options' => [
                            'class' => 'dropdown-menu-right',
                        ],
                        'items' => [
                            '<li role="presentation" class="dropdown-header">Perform action with selected items</li>',
                            '<li>' . Html::a('<span class="glyphicon glyphicon-trash"></span> Delete all selected items', '#', [
                                'onclick' => new JsExpression('(function(e) {
                                    var keys = $("#w9").yiiGridView("getSelectedRows").length;
                                    if(keys > 0) {
                                        $("#ticket-index-form").submit()
                                    } else { 
                                        alert(e);
                                        e.stopPropagation();
                                    }
                                })();'),
                                'data' => [
                                    'confirm' => 'Are you sure you want to delete all selected items?'
                                ],
                            ]) . '</li>',
                        ],
                    ]),
                ]
        ]            
        ],
        'options' => ['id' => 'ticket-index-dynagrid'], // a unique identifier is important
        'id' => 'ticket-index-grid'
    ]); ?>

    <?= $this->render('@app/views/_notification', [
        'session' => $session,
    ]) ?>

    <?= Html::endForm();?>

    <?php Pjax::end(); ?>

</div>
