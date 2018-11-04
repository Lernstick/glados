<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveField;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TicketSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$q_examName = isset(Yii::$app->request->queryParams['TicketSearch']['examName']) ? Yii::$app->request->queryParams['TicketSearch']['examName'] : null;
$q_testTaker = isset(Yii::$app->request->queryParams['TicketSearch']['test_taker']) ? Yii::$app->request->queryParams['TicketSearch']['test_taker'] : null;

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
            ['class' => 'kartik\grid\SerialColumn', 'order' => DynaGrid::ORDER_FIX_LEFT],

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
            'token',
            #'examName',
            [
                'attribute' => 'examName',
                'value' => 'examName',
                'filter' => Select2::widget([
                    'data' => [0 => ['id' => $q_examName, 'text' => $q_examName]],
                    'name' => 'TicketSearch[examName]',
                    #'initValueText' => isset(Yii::$app->request->queryParams['TicketSearch']['examName']) ? Yii::$app->request->queryParams['TicketSearch']['examName'] : null,
                    'theme' => Select2::THEME_BOOTSTRAP,
                    'pluginOptions' => [
                        'allowClear' => true,
                        'minimumInputLength' => 2,
                        'placeholder' => '',
                        'language' => [
                            'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                        ],
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['ticket/index', 'mode' => 'list', 'attr' => 'examName']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) { return {q:params.term}; }')
                        ],
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('function(q) { return q.text; }'),
                        'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                        'initSelection' => new JsExpression('function (e, c) { var d = []; d.push({id: e[0][1].text, text: e[0][1].text}); c(d); }'),
                    ],
                ]),
            ],             
            [
                'attribute' => 'examSubject',
                'filter' => $searchModel->subjectList,
            ],
            'start:timeago',
            'end:timeago',
            #'valid:boolean',
            [
                'attribute' => 'abandoned',
                'format' => 'boolean',
                'filter' => array(
                    'Yes' => 'Yes',
                    'No' => 'No',
                ),
            ],
            #'test_taker',
            [
                'attribute' => 'test_taker',
                'value' => 'test_taker',
                'filter' => Select2::widget([
                    'data' => [0 => ['id' => $q_testTaker, 'text' => $q_testTaker]],
                    'name' => 'TicketSearch[test_taker]',
                    'theme' => Select2::THEME_BOOTSTRAP,
                    'pluginOptions' => [
                        'allowClear' => true,
                        'placeholder' => '',
                        'minimumInputLength' => 2,
                        'language' => [
                            'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                        ],
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['ticket/index', 'mode' => 'list', 'attr' => 'testTaker']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) { return {q:params.term}; }')
                        ],
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('function(q) { return q.text; }'),
                        'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                        'initSelection' => new JsExpression('function (e, c) { var d = []; d.push({id: e[0][1].text, text: e[0][1].text}); c(d); }'),
                    ],
                ]),
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
        ]            
        ],
        'options' => ['id' => 'dynagrid-ticket-index'] // a unique identifier is important
    ]); ?>

    <?= $this->render('@app/views/_notification', [
        'session' => $session,
    ]) ?>

    <?php Pjax::end(); ?>

</div>
