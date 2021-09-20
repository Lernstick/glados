<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $ticketModel app\models\Ticket */
/* @var $searchModel app\models\LogSearch */
/* @var $dataProvider yii\data\ArrayDataProvider */

if (isset($ticketModel)) {
    $filter = [
        'backup' => 'backup',
        'download' => 'download',
        'prepare' => 'prepare',
        'fetch' => 'fetch',
        'restore' => 'restore',
        'screen_capture' => 'screen_capture',
        'keylogger' => 'keylogger',
        'unlock' => 'unlock',
    ];
    $urlCreator = function ($action, $model, $key, $index) {
        if ($action === 'view') {
            return Url::toRoute([
                'log/view',
                'type' => $model->type,
                'date' => $model->date,
                'token' => $model->token,
            ]);
        } else if ($action === 'download') {
            return Url::toRoute([
                'log/download',
                'type' => $model->type,
                'date' => $model->date,
                'token' => $model->token,
            ]);
        }
    };
} else {
    $filter = [
        'glados' => 'glados',
        'error' => 'error',
    ];
    $urlCreator = function ($action, $model, $key, $index) {
        if ($action === 'view') {
            return Url::toRoute([
                'server/log',
                'type' => $model->type,
                'date' => $model->date,
                'token' => $model->token,
            ]);
        } else if ($action === 'download') {
            return Url::toRoute([
                'server/downloadlog',
                'type' => $model->type,
                'date' => $model->date,
                'token' => $model->token,
            ]);
        }
    };
}


?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
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
        ],
        [
            'attribute' => 'type',
            'format' => 'html',
            'filter' => $filter,
        ],
        'path',
        [
            'attribute' => 'size',
            'format' => ['shortSize', 0],
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{view} {download}',
            'buttons' => [
                'view' => function ($url, $model, $key)  {
                    $logButton = "
                        $('#log-show-" . $key . "').click(function(event) {
                            event.preventDefault();
                            $('#logModal').modal('show');
                            $.pjax({url: this.href, container: '#logModalContent', push: false, async:false})
                            $('#logModal').find('.log-file-name').html('".$model->path."');
                        });
                    ";
                    $this->registerJs($logButton);
                    return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                        'id' => 'log-show-' . $key,
                        'title' => \Yii::t('log', 'Show Logfile')
                    ]);
                },
                'download' => function ($url, $model, $key)  {
                    return Html::a('<span class="glyphicon glyphicon-download-alt"></span>', $url, [
                        'title' => \Yii::t('log', 'Download Logfile'),
                        'data-pjax' => '0',
                    ]);
                },
            ],
            'urlCreator' => $urlCreator,
        ],
    ],
    'options' => ['id' => 'grid-server-log-index'], // a unique identifier is important
    'emptyText' => \Yii::t('ticket', 'No log files found.'),
    'pager' => [
        'class' => app\widgets\CustomPager::className(),
        'selectedLayout' => Yii::t('app', '{selected} <span style="color: #737373;">items</span>'),
    ],
]); ?>

<?php Modal::begin([
    'id' => 'logModal',
    'header' => '<h4>' . \Yii::t('log', 'Log') . '&nbsp<code class="log-file-name"></code></h4>',
    'footer' => Html::Button(\Yii::t('log', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]);

    Pjax::begin([
        'id' => 'logModalContent',
    ]);
    Pjax::end();

Modal::end();

?>