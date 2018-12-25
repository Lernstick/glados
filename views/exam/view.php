<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ListView;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\widgets\Pjax;
use yii\helpers\Url;
use miloschuman\highcharts\Highcharts;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Exams', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="exam-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_nav', [
        'model' => $model,
    ]) ?>

    <p></p>

    <div class="tab-content">

        <?php Pjax::begin([
            'id' => 'general',
            'options' => ['class' => 'tab-pane fade in active'],
        ]); ?>

        <?php //Pjax::begin(['id' => 'exam-grid']); ?>

        <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'createdAt:timeago',
                'name',
                'subject',
                [
                    'attribute' => 'time_limit',
                    'format' => 'raw',
                    'value' => $model->time_limit == Null ? 'No Time Limit' : yii::$app->formatter->format($model->{'time_limit'}*60, 'duration'),
                ],
                [
                    'attribute' => 'user.username',
                    'label' => 'Owner',
                    'value' => ( $model->user_id == null ? '<span class="not-set">(user removed)</span>' : '<span>' . $model->user->username . '</span>' ),
                    'format' => 'html',
                    'visible' => Yii::$app->user->can('exam/view/all'),
                ],
                [
                    'attribute' => 'ticketInfo',
                    'format' => 'raw',
                ],
                [
                    'attribute' => 'file_analyzed',
                    'format' => 'raw',
                    'value' => yii::$app->formatter->format($model->file_analyzed, 'ntext'),
                    'visible' => YII_ENV_DEV,
                    'captionOptions' => ['class' => 'dev_item']
                ],
            ],
        ]) ?>

        <?= $this->render('@app/views/_notification', [
            'session' => $session,
        ]) ?>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'file',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'file',
                'value' => Html::a(basename($model->file),
                        ['view', 'id' => $model->id, 'mode' => 'file'],
                        ['data-pjax' => 0]
                    ) . ' ' . (
                    $model->fileConsistency ? 
                    '<span title="' . $model->file . '" class="label label-success">' . 
                    '<span class="glyphicon glyphicon-ok"></span> Test passed</span> ' . Html::a(
                        '<span class="glyphicon glyphicon-search"></span> Browse contents',
                        ['view', 'id' => $model->id, 'mode' => 'browse'],
                        ['data-pjax' => 0]
                    ) : (empty($model->file) || $model->file == null ? 
                    '<span class="not-set">(file not found)</span>' : 
                    '<span title="' . $model->file . '" class="label label-danger">' . 
                    '<span class="glyphicon glyphicon-remove"></span> Test failed</span>')
                ),
                'format' => 'raw',
            ],
            'md5',
            'fileSize:shortSize',
            'fileInfo:html',
        ],
    ]) ?>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'settings',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <div class="alert alert-warning" role="alert"><i class="glyphicon glyphicon-warning-sign"></i> Please notice, all these settings will <b>override</b> the settings configured in the exam file!</div>

    <?= DetailView::widget([
        'model' => $model,
        'template' => '<tr><th{captionOptions}>{value}</th><td{contentOptions}>{label}</td></tr>',
        'attributes' => [
            'grp_netdev:boolean',
            'allow_sudo:boolean',
            'allow_mount:boolean',
            'firewall_off:boolean',
            'screenshots:boolean',
            [
                'label' => 'Screenshot Interval',
                'value' => $model->screenshots_interval*60, # in seconds
                'format' => 'duration'
            ],
            [
                'label' => 'Maximum brightness',
                'value' => $model->max_brightness/100,
                'format' => 'percent'
            ],            
        ],
    ]) ?>

    <?= DetailView::widget([
        'model' => $model,
        'template' => '<tr><th{captionOptions}>{value}</th><td{contentOptions}>{label}</td></tr>',
        'attributes' => [
            'libre_autosave:boolean',
            [
                'label' => 'Libreoffice Autosave Interval',
                'value' => $model->libre_autosave_interval*60, # in seconds
                'format' => 'duration'
            ],
            'libre_createbackup:boolean',
        ],
    ]) ?>

    <?= ListView::widget([
        'dataProvider' => $urlWhitelistDataProvider,
        #'options' => [ 'tag' => 'table', 'class' => 'table table-bordered table-hover'],
        'itemOptions' => [ 'tag' => 'tr' ],
        'itemView' => function ($model, $key, $index, $widget) {
            return '<td>' . $model . '</td>';
        },
        'emptyText' => '<table class="table table-bordered table-hover"><tr><th>HTTP URL Whitelist</th></tr><tr><td>No URLs found.</td></tr></table>',
        'layout' => '<table class="table table-bordered table-hover"><tr><th>HTTP URL Whitelist</th></tr>{items}</table> {pager} {summary}',
    ]); ?>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'monitor',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>
    <?php Pjax::end() ?>

    <div id="chart" class="tab-pane fade">

        <?= Highcharts::widget([
            'options' => [
                'chart' => [
                    'plotBackgroundColor' => null,
                    'plotBorderWidth' => null,
                    'plotShadow' => false,
                    'type' => 'pie',
                    'events' => [
                        'load' => new JsExpression("function () {
                            var s = this.series[0];
                            //vat t = this;
                            setInterval(function () {
                                $.getJSON('index.php?r=exam/view&mode=json&id=$model->id', function (jsondata) {
                                    s.setData(jsondata);
                                    //t.setData(jsondata);
                                });
                            }, 15000);
                        }" )
                    ],
                ],
                'title' => [
                    'text' => 'Tickets for Exam "' . $model->name . '" in "' . $model->subject . '"'
                ],
                'tooltip' => [
                    'pointFormat' => '{series.name}: <b>{point.percentage:.1f}%</b>'
                ],
                'plotOptions' => [
                    'pie' => [
                        'allowPointSelect' => true,
                        'cursor' => 'pointer',
                        'dataLabels' => [
                            'enabled' => false
                        ],
                        'showInLegend' => true
                    ]
                ],
                'series' => [
                    [
                        'name' => 'Tickets',
                        'colorByPoint' => true,
                        'data' => [
                            [
                                'name' => 'Open',
                                'y' => $model->openTicketCount,
                                'color' => '#5cb85c'
                            ],
                            [
                                'name' => 'Running',
                                'y' => $model->runningTicketCount,
                                'color' => '#286090',
                                'sliced' => true,
                                'selected' => true
                            ],
                            [
                                'name' => 'Closed',
                                'y' => $model->closedTicketCount,
                                'color' => '#d9534f'
                            ],
                            [
                                'name' => 'Submitted',
                                'y' => $model->submittedTicketCount,
                                'color' => '#f0ad4e'
                            ],
                            [
                                'name' => 'Unknown',
                                'y' => $model->ticketCount - $model->openTicketCount - $model->runningTicketCount - $model->closedTicketCount - $model->submittedTicketCount,
                                'color' => '#dddddd'
                            ]

                        ]
                    ]
                ]
            ]
        ]); ?>

    </div>

</div>

