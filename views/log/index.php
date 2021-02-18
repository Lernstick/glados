<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use yii\widgets\Pjax;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LogSearch */
/* @var $dataProvider yii\data\ArrayDataProvider */


?>

<?= DynaGrid::widget([
    'showPersonalize' => false,
    'columns' => [
        ['class' => 'kartik\grid\SerialColumn', 'order' => DynaGrid::ORDER_FIX_LEFT],
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
            'filter' => array(
                'backup' => 'backup',
                'download' => 'download',
                'fetch' => 'fetch',
                'restore' => 'restore',
            ),
        ],
        'path',
        [
            'class' => 'yii\grid\ActionColumn',
            'order' => DynaGrid::ORDER_FIX_RIGHT,
            'template' => '{view} {download}',
            'buttons' => [
                'view' => function ($url, $model, $key)  {
                    $logButton = "
                        $('#log-show-" . $key . "').click(function(event) {
                            event.preventDefault();
                            $('#logModal').modal('show');
                            $.pjax({url: this.href, container: '#logModalContent', push: false, async:false})
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
            'urlCreator' => function ($action, $model, $key, $index) {
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
            },
        ],
    ],
    'storage' => DynaGrid::TYPE_COOKIE,
    'theme' => 'simple-default',
    'gridOptions' => [
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
    ],
    'options' => ['id' => 'dynagrid-ticket-log-index'] // a unique identifier is important
]); ?>

<?php Modal::begin([
    'id' => 'logModal',
    'header' => '<h4>' . \Yii::t('log', 'Log') . '</h4>',
    'footer' => Html::Button(\Yii::t('log', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]);

    Pjax::begin([
        'id' => 'logModalContent',
    ]);
    Pjax::end();

Modal::end();

?>