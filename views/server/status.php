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

$this->title = \Yii::t('server', 'Server Status');
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['status']];

$gaugeOptions = [
    'chart' => [
        'type' => 'solidgauge',
        'height' => '180px',
        'events' => [
            'load' => new JsExpression("function () {
                var s = this.series[0];
                var yAxis = this.yAxis[0];
                setInterval(function () {
                    var y = isNaN(parseFloat($('#reload-load').data(s.name).y))
                        ? parseFloat($('#reload-load').data(s.name))
                        : parseFloat($('#reload-load').data(s.name).y);
                    var max = isNaN(parseFloat($('#reload-load').data(s.name).max))
                        ? yAxis.max
                        : parseFloat($('#reload-load').data(s.name).max);
                    var d = [{'y': y, 'max': max}];
                    yAxis.update({'min': 0, 'max': max});
                    s.setData(d);
                }, 1000);
            }" )
        ],
    ],
    'title' => [
        'text' => null,
        'floating' => true,
        'style' => [
            'color' => '#333333',
            'fontSize' => '14px',
        ],
    ],
    'subtitle' => [
        'text' => null,
        'floating' => true,
        'y' => 45,
    ],
    'pane' => [
        'center' => ['50%', '85%'],
        'size' => '100%',
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
        'tickPositions' => [],
        'title' => [
            'y' => -70,
        ],
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
            <div class="col-sm-2">
                <?= Highcharts::widget([
                    'scripts' => [
                        'highcharts-more', // enables supplementary chart types (gauge, arearange, columnrange, etc.)
                        'modules/solid-gauge',
                    ],
                    'options' => ArrayHelper::merge($gaugeOptions, [
                        'title' => ['text' => $model->getAttributeLabel('procTotal')],
                        'yAxis' => ['max' => $model->procMaximum],
                        'series' => [
                            [
                                'name' => 'proc_total',
                                'data' => [
                                    [
                                        'y' => $model->procTotal,
                                        'max' => $model->procMaximum,
                                    ],
                                ],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">{point.y}/{point.max}</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">' .
                                                \Yii::t('server', 'processes') .
                                            '</span>' .
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
                        'title' => ['text' => $model->getAttributeLabel('db_threads_connected')],
                        'yAxis' => ['max' => $model->db_max_connections],
                        'series' => [
                            [
                                'name' => 'db_threads_connected',
                                'data' => [
                                    [
                                        'y' => $model->db_threads_connected,
                                        'max' => $model->db_max_connections,
                                    ],
                                ],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">{point.y}/{point.max}</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">' .
                                                \Yii::t('server', 'connections') .
                                            '</span>' .
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
                        'title' => ['text' => $model->getAttributeLabel('runningDaemons')],
                        'yAxis' => ['max' => \Yii::$app->params['maxDaemons']],
                        'series' => [
                            [
                                'name' => 'running_daemons',
                                'data' => [
                                    [
                                        'y' => $model->runningDaemons,
                                        'max' => \Yii::$app->params['maxDaemons'],
                                    ],
                                ],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">{point.y}/{point.max}</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">' .
                                                \Yii::t('server', 'daemons') .
                                            '</span>' .
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
                        'title' => ['text' => $model->getAttributeLabel('memTotal')],
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
                        'title' => ['text' => $model->getAttributeLabel('swapTotal')],
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
                        'title' => ['text' => $model->getAttributeLabel('cpuPercentage')],
                        'subtitle' => ['text' => \Yii::t('server', 'Ø of {n} cores', ['n' => $model->ncpu])],
                        'yAxis' => ['max' => 100],
                        'series' => [
                            [
                                'name' => 'cpu_percentage',
                                'data' => [intval($model->cpuPercentage)],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">{y:.1f}</span><br/>' .
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
                        'title' => ['text' => $model->getAttributeLabel('ioPercentage')],
                        'yAxis' => ['max' => 100],
                        'series' => [
                            [
                                'name' => 'io_percentage',
                                'data' => [intval($model->ioPercentage)],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">{y:.1f}</span><br/>' .
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
                        'title' => ['text' => $model->getAttributeLabel('inotify_active_watches')],
                        'yAxis' => ['max' => $model->inotify_max_user_watches],
                        'series' => [
                            [
                                'name' => 'inotify_active_watches',
                                'data' => [
                                    [
                                        'y' => $model->inotify_active_watches,
                                        'max' => $model->inotify_max_user_watches,
                                    ],
                                ],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">≈{point.y}/{point.max}</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">' .
                                                \Yii::t('server', 'watches') .
                                            '</span>' .
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
                        'title' => ['text' => $model->getAttributeLabel('inotify_active_instances')],
                        'yAxis' => ['max' => $model->inotify_max_user_instances],
                        'series' => [
                            [
                                'name' => 'inotify_active_instances',
                                'data' => [
                                    [
                                        'y' => $model->inotify_active_instances,
                                        'max' => $model->inotify_max_user_instances,
                                    ],
                                ],
                                'dataLabels' => [
                                    'format' =>
                                        '<div style="text-align:center">' .
                                            '<span style="font-size:11px">≈{point.y}/{point.max}</span><br/>' .
                                            '<span style="font-size:10px;opacity:0.4">' .
                                                \Yii::t('server', 'instances') .
                                            '</span>' .
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
                            'title' => ['text' => $model->getAttributeLabel('diskUsed')],
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
