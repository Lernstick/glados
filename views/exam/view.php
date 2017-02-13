<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
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
<div class="exam-view container">

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
                'name',
                'subject',
                [
                    'attribute' => 'file',
                    'value' => basename($model->file, '.squashfs') . ' ' . (
                        $model->fileConsistency ? 
                        '<span title="' . $model->file . '" class="label label-success">' . 
                        '<span class="glyphicon glyphicon-ok"></span> Test passed</span> ' . Html::a(
                            '<span class="glyphicon glyphicon-search"></span> Browse contents',
                            ['view', 'id' => $model->id, 'mode' => 'squashfs'],
                            ['data-pjax' => 0]
                        ) :
                        '<span title="' . $model->file . '" class="label label-danger">' . 
                        '<span class="glyphicon glyphicon-remove"></span> Test failed</span>'
                    ),
                    'format' => 'raw',
                ],
                'md5',
                'fileSize:shortSize',
                'fileInfo:html',
                [
                    'attribute' => 'user.username',
                    'label' => 'Owner',
                    'value' => ( $model->user_id == null ? '<span class="not-set">(user removed)</span>' : '<span>' . $model->user->username . '</span>' ),
                    'format' => 'html',
                    'visible' => Yii::$app->user->can('exam/view/all'),
                ],
                [
                    'attribute' => 'ticketCount',
    		        'format' => 'raw',
                    'value' => Html::a(
                        $model->ticketCount,
                        ['ticket/index', 'TicketSearch[examName]' => $model->name, 'TicketSearch[examSubject]' => $model->subject],
                        ['data-pjax' => 0]
                    )
                ],
                'openTicketCount',
                'runningTicketCount',
                'closedTicketCount',
                'submittedTicketCount',
            ],
        ]) ?>

        <?= $this->render('@app/views/_notification', [
            'session' => $session,
        ]) ?>


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

