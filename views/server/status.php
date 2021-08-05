<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;
use yii\web\JsExpression;
use miloschuman\highcharts\Highcharts;
use yii\helpers\ArrayHelper;
use app\models\DaemonSearch;
use app\components\ActiveEventField;
use yii\widgets\MaskedInput;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ServerStatus */

$js = <<< SCRIPT
function check_load() {
    $.pjax.reload({container: "#server_status", async: false})
}

setInterval(check_load, $model->interval*1000);

SCRIPT;

$this->registerJs($js, \yii\web\View::POS_READY);

$this->title = \Yii::t('server', 'System Status');
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['system']];

$gaugeOptions = [
    'chart' => [
        'type' => 'solidgauge',
        'height' => '180px',
        'events' => [
            'load' => new JsExpression("function () {
                var s = this.series[0];
                setInterval(function () {
                    s.setData([parseFloat($('#reload-load').data(s.name))]);
                }, 1000);
            }" )
        ],
    ],
    'title' => ['text' => null],
    'pane' => [
        'center' => ['50%', '85%'],
        'size' => '150%',
        'startAngle' => -90,
        'endAngle' => 90,
        'background' => [
            'backgroundColor' => '#EEE',
            'innerRadius' => '60%',
            'outerRadius' => '100%',
            'shape' => 'arc',
        ],
    ],
    'exporting' => [
        'enabled' => false,
    ],
    'tooltip' => [
        'enabled' => false,
    ],
    'yAxis' => [
        'min' => 0,
        'max' => 100,
        'stops' => [
            [0.1, '#55BF3B'], // green
            [0.5, '#DDDF0D'], // yellow
            [0.8, '#DF5353'] // red
        ],
        'lineWidth' => 0,
        'tickWidth' => 0,
        'minorTickInterval' => null,
        'tickPositions' => [],//[0, $model->procMaximum],
        'labels' => [
            'y' => 20,
            'x' => 0,
        ],
    ],
    'plotOptions' => [
        'solidgauge' => [
            'dataLabels' => [
                'useHTML' => true,
                'borderWidth' => 0,
                'y' => 5,
            ],
        ]
    ],
    'credits' => [
        'enabled' => false,
    ],
];

$daemons = new DaemonSearch();
$runningDaemons = $daemons->search([])->totalCount;

Pjax::begin([
    'id' => 'server_status',
    'options' => ['class' => 'hidden'],
]);

$options = [
    'id' => 'reload-load',
    'class' => 'btn btn-default btn-xs pull-right',
    'data-proc_total' => $model->procTotal,
    'data-db_threads_connected' => $model->db_threads_connected,
    'data-running_daemons' => $runningDaemons,
    'data-mem_used' => $model->memUsed/1048576, # MB
    'data-swap_used' => $model->swapUsed/1048576, # MB
    'data-cpu_percentage' => $model->cpuPercentage, # %
    'data-inotify_active_watches' => $model->inotify_active_watches,
    'data-inotify_active_instances' => $model->inotify_active_instances,
];

foreach($model->diskTotal as $key => $disk) {
    $options['disk_usage_' . $key] = $model->diskUsed[$key]/1073741824;
}

    echo Html::a('<i class="glyphicon glyphicon-refresh"></i>', '', $options);

Pjax::end();

?>

<div class="status-view container">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'action' => Url::to(['server/status']),
    ]); ?>

        <div class="col-sm-8">&nbsp;</div>
        <div class="col-sm-4">
            <?= $form->field($model, 'interval', [
                'template' => 
                    '<div class="input-group">' .
                        "{label}\n{input}\n{hint}".
                        '<span class="input-group-btn">'.
                            '<button class="btn btn-default" type="submit">' . \Yii::t('app', 'Set') . '</button>'.
                        '</span>'.
                    '</div>'.
                    "\n{error}",
                'labelOptions' => ['class' => 'input-group-addon'],
                'hintOptions' => ['class' => 'input-group-addon'],
            ])->textInput()->widget(MaskedInput::classname(), [
                'mask' => '9{1,3}',
                'options' => ['class' => 'form-control'],
            ]); ?>
        </div>

    <?php ActiveForm::end(); ?>

    <hr>

    <div class="tab-content">
        <div class="row">
            <div class="col-sm-2">
                <?= Highcharts::widget([
                    'scripts' => [
                        'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                        'modules/solid-gauge',
                    ],
                    'options' => ArrayHelper::merge($gaugeOptions, [
                        'title' => ['text' => \Yii::t('server', 'Webserver processes')],
                        'yAxis' => ['max' => $model->procMaximum],
                        'series' => [
                            [
                                'name' => 'proc_total',
                                'data' => [$model->procTotal],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">' .
                                                substitute('{y}/{max}', ['max' => $model->procMaximum]) .
                                            '</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">processes</span>' .
                                        '</div>',
                                ],
                            ]
                        ]
                    ]),
                ]); ?>
            </div>
            <div class="col-sm-2">
                <?= Highcharts::widget([
                    'scripts' => [
                        'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                        'modules/solid-gauge',
                    ],
                    'options' => ArrayHelper::merge($gaugeOptions, [
                        'title' => ['text' => \Yii::t('server', 'Database connections')],
                        'yAxis' => ['max' => $model->db_max_connections],
                        'series' => [
                            [
                                'name' => 'db_threads_connected',
                                'data' => [$model->db_threads_connected],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">' .
                                                substitute('{y}/{max}', ['max' => $model->db_max_connections]) .
                                            '</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">connections</span>' .
                                        '</div>',
                                ],
                            ]
                        ]
                    ]),
                ]); ?>
            </div>
            <div class="col-sm-2">
                <?= Highcharts::widget([
                    'scripts' => [
                        'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                        'modules/solid-gauge',
                    ],
                    'options' => ArrayHelper::merge($gaugeOptions, [
                        'title' => ['text' => \Yii::t('server', 'Running daemons')],
                        'yAxis' => ['max' => \Yii::$app->params['maxDaemons']],
                        'series' => [
                            [
                                'name' => 'running_daemons',
                                'data' => [$runningDaemons],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">' .
                                                substitute('{y}/{max}', ['max' => \Yii::$app->params['maxDaemons']]) .
                                            '</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">daemons</span>' .
                                        '</div>',
                                ],
                            ]
                        ]
                    ]),
                ]); ?>
            </div>
            <div class="col-sm-2">
                <?= Highcharts::widget([
                    'scripts' => [
                        'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                        'modules/solid-gauge',
                    ],
                    'options' => ArrayHelper::merge($gaugeOptions, [
                        'title' => ['text' => \Yii::t('server', 'Memory usage')],
                        'yAxis' => ['max' => intval($model->memTotal/1048576)],
                        'series' => [
                            [
                                'name' => 'mem_used',
                                'data' => [intval($model->memUsed/1048576)],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">' .
                                                substitute('{y:.1f}/{max}', ['max' => intval($model->memTotal/1048576)]) .
                                            '</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">MB</span>' .
                                        '</div>',
                                ],
                            ]
                        ]
                    ]),
                ]); ?>
            </div>
            <div class="col-sm-2">
                <?= Highcharts::widget([
                    'scripts' => [
                        'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                        'modules/solid-gauge',
                    ],
                    'options' => ArrayHelper::merge($gaugeOptions, [
                        'title' => ['text' => \Yii::t('server', 'Swap usage')],
                        'yAxis' => ['max' => intval($model->swapTotal/1048576)],
                        'series' => [
                            [
                                'name' => 'swap_used',
                                'data' => [intval($model->swapUsed/1048576)],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">' .
                                                substitute('{y:.1f}/{max}', ['max' => intval($model->swapTotal/1048576)]) .
                                            '</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">MB</span>' .
                                        '</div>',
                                ],
                            ]
                        ]
                    ]),
                ]); ?>
            </div>
            <div class="col-sm-2">
                <?= Highcharts::widget([
                    'scripts' => [
                        'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                        'modules/solid-gauge',
                    ],
                    'options' => ArrayHelper::merge($gaugeOptions, [
                        'title' => ['text' => \Yii::t('server', 'CPU usage')],
                        'yAxis' => ['max' => 100],
                        'series' => [
                            [
                                'name' => 'cpu_percentage',
                                'data' => [intval($model->cpuPercentage)],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">' .
                                                substitute('{y:.1f}/{max}', ['max' => 100]) .
                                            '</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">%</span>' .
                                        '</div>',
                                ],
                            ]
                        ]
                    ]),
                ]); ?>
            </div>
            <div class="col-sm-2">
                <?= Highcharts::widget([
                    'scripts' => [
                        'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                        'modules/solid-gauge',
                    ],
                    'options' => ArrayHelper::merge($gaugeOptions, [
                        'title' => ['text' => \Yii::t('server', 'Inotify watches')],
                        'yAxis' => ['max' => $model->inotify_max_user_watches],
                        'series' => [
                            [
                                'name' => 'inotify_active_watches',
                                'data' => [$model->inotify_active_watches],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">' .
                                                substitute('~{y}/{max}', ['max' => $model->inotify_max_user_watches]) .
                                            '</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">watches</span>' .
                                        '</div>',
                                ],
                            ]
                        ]
                    ]),
                ]); ?>
            </div>
            <div class="col-sm-2">
                <?= Highcharts::widget([
                    'scripts' => [
                        'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                        'modules/solid-gauge',
                    ],
                    'options' => ArrayHelper::merge($gaugeOptions, [
                        'title' => ['text' => \Yii::t('server', 'Inotify instances')],
                        'yAxis' => ['max' => $model->inotify_max_user_instances],
                        'series' => [
                            [
                                'name' => 'inotify_active_instances',
                                'data' => [$model->inotify_active_instances],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">' .
                                                substitute('~{y}/{max}', ['max' => $model->inotify_max_user_instances]) .
                                            '</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">instances</span>' .
                                        '</div>',
                                ],
                            ]
                        ]
                    ]),
                ]); ?>
            </div>

            <?php foreach($model->diskTotal as $key => $disk) { ?>
                <div class="col-sm-2">
                    <?= Highcharts::widget([
                        'scripts' => [
                            'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                            'modules/solid-gauge',
                        ],
                        'options' => ArrayHelper::merge($gaugeOptions, [
                            'title' => [
                                'text' => \Yii::t('server', 'Disk usage'),
                            ],
                            'subtitle' => [
                                'text' => $model->diskPath[$key],
                            ],
                            'yAxis' => ['max' => $model->diskTotal[$key]/1073741824],
                            'series' => [
                                [
                                    'name' => 'disk_usage_' . $key,
                                    'data' => [$model->diskUsed[$key]/1073741824],
                                    'dataLabels' => [
                                        'format' =>
                                            '<div style="text-align:center">' .
                                                '<span style="font-size:11px">' .
                                                    substitute('{y:.2f}/{max}', [
                                                        'max' => yii::$app->formatter->format($model->diskTotal[$key]/1073741824, ['decimal', 2]),
                                                    ]) .
                                                '</span><br/>' .
                                                '<span style="font-size:10px;opacity:0.4">GB</span>' .
                                            '</div>',
                                    ],
                                ]
                            ]
                        ]),
                    ]); ?>
                </div>
            <?php } ?>

        </div>
    </div>
</div>
