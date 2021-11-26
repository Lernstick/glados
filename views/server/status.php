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
use miloschuman\highcharts\Highstock;

/* @var $this yii\web\View */
/* @var $model app\models\ServerStatus */

$js = <<< SCRIPT
function check_load() {
    $.pjax.reload({container: "#server_status", async: false})
}

setInterval(check_load, $model->interval*1000);

SCRIPT;

$this->registerJs($js, \yii\web\View::POS_READY);

$this->title = \Yii::t('server', 'Server Status');
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['status']];

$stockOptions = [
    'chart' => [
        'height' => (12 / 16 * 100) . '%', // 16:12 ratio
        'panning' => ['enabled' => false],
        'backgroundColor' => 'transparent',
        //'plotBorderColor' => '#7cb5ec', // lightblue
        'plotBorderWidth' => 1,
        'events' => [
            'load' => new JsExpression("function () {
                var chart = this;
                var s = this.series[0];
                var yAxis = this.yAxis;
                var xAxis = this.xAxis;
                var store = 3600; // store 3600 data points (1h of data)

                if (typeof(Storage) !== 'undefined') {
                    var data = window.localStorage.getItem(s.name);
                    if (data !== null) {
                        s.setData(JSON.parse(data));
                    }
                }

                function update_graph() {
                    let x = (new Date()).getTime();
                    let y = parseFloat($('#reload-load').data(s.name).y);
                    let max = parseFloat($('#reload-load').data(s.name).max);
                    yAxis.forEach(axis => axis.update({'min': 0, 'max': max}));
                    s.addPoint([x, y], true, s.data.length >= store);
                    let first_element = s.options.data.slice(-60)[0];
                    if (first_element !== null) {
                        xAxis.forEach(axis => axis.setExtremes(s.options.data.slice(-60)[0][0], x));
                    }
                }

                // update the graph every second
                update_graph();
                setInterval(update_graph, 1000);

                // store the data points all 10 seconds
                setInterval(function () {
                    window.localStorage.setItem(s.name, JSON.stringify(s.options.data.slice(-store)));
                }, 10000);

            }" ),
        ],
    ],
    'title' => [
        'floating' => true,
        'align' => 'center',
        'verticalAlign' => 'top',
        'y' => 20,
        'text' => 'small',
    ],
    'subtitle' => [
        'floating' => true,
        'align' => 'center',
        'verticalAlign' => 'top',
        'y' => 35,
    ],
    'xAxis' => [
        'labels' => ['enabled' => false],
        'tickColor' => 'transparent',
    ],
    'yAxis' => [
        'labels' => ['enabled' => false],
        'endOnTick' => false,
        'gridLineColor' => 'transparent',
        'min' => 0,
        'max' => 100,
    ],
    'exporting' => ['enabled' => false],
    'time' => ['useUTC' => false],
    'credits' => ['enabled' => false],
    'scrollbar' => ['enabled' => false],
    'navigator' => ['enabled' => false],
    'rangeSelector' => ['enabled' => false],
    'tooltip' => ['enabled' => false],
    'plotOptions' => [
        'series' => [
            'fillOpacity' => 0.1,
            'lineWidth' => 1,
            'enableMouseTracking' => false,
            //'color' => '#7cb5ec',
        ],
    ],
];

Pjax::begin([
    'id' => 'server_status',
    'options' => ['class' => 'hidden'],
]);

    echo Html::a('<i class="glyphicon glyphicon-refresh"></i>', '', [
        'id' => 'reload-load',
        'class' => 'btn btn-default btn-xs pull-right',
        'data' => $model->data,
    ]);

Pjax::end();

?>

<div class="status-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning" role="alert">
                <?= \Yii::t('server', 'If one of the metrics below reaches 80% or more for a longer time or you experience performance issues, please consult the following resources for more information on how to solve these problems:') ?>
                <ul>
                    <li>
                        <?= Html::a(\Yii::t('server', 'Manual / Hardware Recommendations'), ['/howto/view', 'id' => 'hardware-recommendations.md'], ['class' => 'alert-link', 'target' => '_new']) ?>
                    </li>
                    <li>
                        <?= Html::a(\Yii::t('server', 'Manual / Large exams'), ['/howto/view', 'id' => 'large-exams.md'], ['class' => 'alert-link', 'target' => '_new']) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'action' => Url::to(['server/status']),
    ]); ?>

        <div class="col-sm-8">&nbsp;</div>
        <div class="col-sm-4">
            <?= $form->field($model, 'interval', [
                'template' => 
                    '<div class="input-group">' .
                        "{label}\n{input}\n{hint}" .
                        '<span class="input-group-btn">' .
                            '<button class="btn btn-default" type="submit">' .
                                \Yii::t('app', 'Set') .
                            '</button>' .
                        '</span>' .
                    '</div>' .
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
            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'title' => ['text' => $model->getAttributeLabel('procTotal')],
                        'chart' => ['plotBorderColor' => '#b2b80d'], // yellow
                        'yAxis' => ['max' => $model->procMaximum],
                        'series' => [
                            [
                                'type' => 'area',
                                'color' => '#b2b80d', // yellow
                                'name' => 'proc_total',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, $model->procTotal]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('{y}/{max} ".\Yii::t('server', 'processes')." ({percent}%)', {
                                                y: e.point.y.toFixed(0),
                                                max: e.target.yAxis.max.toFixed(0),
                                                percent: p.toFixed(0)
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'title' => ['text' => $model->getAttributeLabel('db_threads_connected')],
                        'chart' => ['plotBorderColor' => '#b2b80d'], // yellow
                        'yAxis' => ['max' => $model->db_max_connections],
                        'series' => [
                            [
                                'type' => 'area',
                                'color' => '#b2b80d', // yellow
                                'name' => 'db_threads_connected',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, $model->db_threads_connected]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('{y}/{max} ".\Yii::t('server', 'connections')." ({percent}%)', {
                                                y: e.point.y.toFixed(0),
                                                max: e.target.yAxis.max.toFixed(0),
                                                percent: p.toFixed(0)
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'title' => ['text' => $model->getAttributeLabel('runningDaemons')],
                        'yAxis' => ['max' => \Yii::$app->params['maxDaemons']],
                        'series' => [
                            [
                                'type' => 'area',
                                'name' => 'running_daemons',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, $model->runningDaemons]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('{y}/{max} ".\Yii::t('server', 'daemons')." ({percent}%)', {
                                                y: e.point.y.toFixed(0),
                                                max: e.target.yAxis.max.toFixed(0),
                                                percent: p.toFixed(0)
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'chart' => ['plotBorderColor' => '#7cb5ec'], // lightblue
                        'title' => ['text' => $model->getAttributeLabel('averageLoad')],
                        'series' => [
                            [
                                'type' => 'area',
                                'color' => '#7cb5ec',
                                'name' => 'average_load',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, $model->averageLoad]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('{y} %', {
                                                y: e.point.y.toFixed(0),
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'title' => ['text' => $model->getAttributeLabel('memTotal')],
                        'chart' => ['plotBorderColor' => '#8b12ae'], // purple
                        'yAxis' => ['max' => intval($model->memTotal/1048576)],
                        'series' => [
                            [
                                'type' => 'area',
                                'color' => '#8b12ae', // purple
                                'name' => 'mem_used',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, intval($model->memUsed/1048576)]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('{y}/{max} MB ({percent}%)', {
                                                y: e.point.y.toFixed(0),
                                                max: e.target.yAxis.max.toFixed(0),
                                                percent: p.toFixed(0)
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'title' => ['text' => $model->getAttributeLabel('swapTotal')],
                        'chart' => ['plotBorderColor' => '#8b12ae'], // purple
                        'yAxis' => ['max' => intval($model->swapTotal/1048576)],
                        'series' => [
                            [
                                'type' => 'area',
                                'color' => '#8b12ae', // purple
                                'name' => 'swap_used',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, intval($model->swapUsed/1048576)]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('{y}/{max} MB ({percent}%)', {
                                                y: e.point.y.toFixed(0),
                                                max: e.target.yAxis.max.toFixed(0),
                                                percent: p.toFixed(0)
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'title' => ['text' => $model->getAttributeLabel('cpuPercentage')],
                        'chart' => ['plotBorderColor' => '#7cb5ec'], // lightblue
                        'series' => [
                            [
                                'type' => 'area',
                                'color' => '#7cb5ec',
                                'name' => 'cpu_percentage',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, $model->cpuPercentage]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('{y} % (".\Yii::t('server', 'Ø of {n} cores').")', {
                                                y: e.point.y.toFixed(0),
                                                n: ".$model->ncpu."
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'title' => ['text' => $model->getAttributeLabel('ioPercentage')],
                        'chart' => ['plotBorderColor' => '#4da60c'], // lightgreen
                        'series' => [
                            [
                                'type' => 'area',
                                'color' => '#4da60c', // lightgreen
                                'name' => 'io_percentage',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, $model->ioPercentage]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('{y} %', {
                                                y: e.point.y.toFixed(0),
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'title' => ['text' => $model->getAttributeLabel('inotify_active_watches')],
                        'chart' => ['plotBorderColor' => '#4da60c'], // lightgreen
                        'yAxis' => ['max' => $model->inotify_max_user_watches],
                        'series' => [
                            [
                                'type' => 'area',
                                'color' => '#4da60c', // lightgreen
                                'name' => 'inotify_active_watches',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, $model->inotify_active_watches]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('≈{y}/{max} ".\Yii::t('server', 'watches')." ({percent}%)', {
                                                y: e.point.y.toFixed(0),
                                                max: e.target.yAxis.max.toFixed(0),
                                                percent: p.toFixed(0)
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <div class="col-sm-3">
                <?= Highstock::widget([
                    'scripts' => [],
                    'options' => ArrayHelper::merge($stockOptions, [
                        'title' => ['text' => $model->getAttributeLabel('inotify_active_instances')],
                        'chart' => ['plotBorderColor' => '#4da60c'], // lightgreen
                        'yAxis' => ['max' => $model->inotify_max_user_instances],
                        'series' => [
                            [
                                'type' => 'area',
                                'color' => '#4da60c', // lightgreen
                                'name' => 'inotify_active_instances',
                                'data' => array_merge(
                                    array_fill(0, 100, null),
                                    [0 => [microtime(true)*1000, $model->inotify_active_instances]]
                                ),
                                'events' => [
                                    'addPoint' => new JsExpression("function (e) {
                                        var p = 100*e.point.y/e.target.yAxis.max;
                                        e.target.chart.setTitle(null, {
                                            text: substitute('≈{y}/{max} ".\Yii::t('server', 'instances')." ({percent}%)', {
                                                y: e.point.y.toFixed(0),
                                                max: e.target.yAxis.max.toFixed(0),
                                                percent: p.toFixed(0)
                                            })
                                        });
                                    }" ),
                                ],
                            ],
                        ]
                    ]),
                ]); ?>
            </div>

            <?php foreach($model->diskTotal as $key => $disk) { ?>
                <div class="col-sm-3">
                    <?= Highstock::widget([
                        'scripts' => [],
                        'options' => ArrayHelper::merge($stockOptions, [
                            'title' => ['text' => $model->getAttributeLabel('diskUsed')],
                            'chart' => ['plotBorderColor' => '#4da60c'], // lightgreen
                            'yAxis' => ['max' => $model->diskTotal[$key]/1073741824],
                            'series' => [
                                [
                                    'type' => 'area',
                                    'color' => '#4da60c', // lightgreen
                                    'name' => 'disk_usage_' . $key,
                                    'data' => array_merge(
                                        array_fill(0, 100, null),
                                        [0 => [microtime(true)*1000, $model->diskUsed[$key]/1073741824]]
                                    ),
                                    'events' => [
                                        'addPoint' => new JsExpression("function (e) {
                                            var p = 100*e.point.y/e.target.yAxis.max;
                                            e.target.chart.setTitle(null, {
                                                text: substitute('".$model->diskPath[$key].": {y}/{max} GB ({percent}%)', {
                                                    y: e.point.y.toFixed(2),
                                                    max: e.target.yAxis.max.toFixed(2),
                                                    percent: p.toFixed(0)
                                                })
                                            });
                                        }" ),
                                    ],
                                ],
                            ]
                        ]),
                    ]); ?>
                </div>
            <?php } ?>

            <?php foreach($model->netName as $key => $dev) { ?>
                <div class="col-sm-3">
                    <?= Highstock::widget([
                        'scripts' => [],
                        'options' => ArrayHelper::merge($stockOptions, [
                            'title' => ['text' => $model->getAttributeLabel('netUsage')],
                            'chart' => ['plotBorderColor' => '#a74f01'], // brown
                            'yAxis' => ['max' => $model->netMaxSpeed[$key]/1073741824],
                            'series' => [
                                [
                                    'type' => 'area',
                                    'color' => '#a74f01', // brown
                                    'name' => 'net_usage_' . $key,
                                    'data' => array_merge(
                                        array_fill(0, 100, null),
                                        [0 => [microtime(true)*1000, $model->netCurrentSpeed[$key]/1073741824]]
                                    ),
                                    'events' => [
                                        'addPoint' => new JsExpression("function (e) {
                                            var p = 100*e.point.y/e.target.yAxis.max;
                                            e.target.chart.setTitle(null, {
                                                text: substitute('".$model->netName[$key].": {y} MB/s ({percent}%)', {
                                                    y: e.point.y.toFixed(2),
                                                    percent: p.toFixed(0)
                                                })
                                            });
                                        }" ),
                                    ],
                                ],
                            ]
                        ]),
                    ]); ?>
                </div>
            <?php } ?>

        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <samp><?= $model->uname() ?></samp>
        </div>
    </div>
</div>
