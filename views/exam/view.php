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

    <div class="row">

    <?php if ( (empty($model->file) || $model->file == null) && (empty($model->file2) || $model->file2 == null) ) { ?>

        <div class="col-sm-12 col-md-6">
          <div class="panel panel-danger">
            <div class="panel-heading">
              <span>Exam file</span>
            </div>
            <div class="panel-body">
              <p>
                <span class="not-set">No file found</span>
              </p>
              <p class="text-muted">
                <span>You have to provide at least one exam file.</span><br>
                <span class="glyphicon glyphicon-alert"></span>
                <span>For more information, please visit <?= Html::a('Manual / Create an exam', ['/howto/view', 'id' => 'create-exam.md'], ['class' => 'alert-link']) ?>.</span>
              </p>
              <p>
                  <?= Html::a(
                    '<span class="glyphicon glyphicon-plus"></span> Add file ...',
                    ['update', 'id' => $model->id, '#' => 'tab_file'],
                    ['data-pjax' => 0, 'class' => 'btn btn-default', 'role' => 'button']
                ); ?>
              </p>
            </div>
          </div>
        </div>

    <?php } if (!empty($model->file2)) { ?>

      <div class="col-sm-12 col-md-6">
        <div class="panel <?= ($model->file2Consistency ? 'panel-default' : 'panel-danger'); ?>">
          <div class="panel-heading">
            <span><?= $model->getattributeLabel('file2')?></span>
          </div>
          <div class="panel-body">
            <p>
                <?= '<span class="glyphicon glyphicon-file"></span> ' . basename($model->file2); ?>
            </p>
            <p>
                <span class="text-muted">Consistency: </span><?= ($model->file2Consistency ? 
                    '<span class="text-success">File valid</span>' :  
                    '<strong><span class="not-set">File not valid</span></strong>'
                ) ?><br>
                <span class="text-muted">Size: <?= yii::$app->formatter->format($model->file2Size, 'shortSize') ?></span><br>
                <span class="text-muted"><?= yii::$app->formatter->format($model->file2Info, 'html') ?></span>
            </p>
            <p>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-search"></span> Browse',
                    ['view', 'id' => $model->id, 'mode' => 'browse', 'type' => 'zip'],
                    [
                        'data-pjax' => 0,
                        'class' => [
                            'btn',
                            'btn-default',
                            $model->file2Consistency ? null : 'disabled',
                        ],
                        'role' => 'button'
                    ]
                ); ?>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-download-alt"></span> Download',
                    ['view', 'id' => $model->id, 'mode' => 'file', 'type' => 'zip'],
                    ['data-pjax' => 0, 'class' => 'btn btn-default', 'role' => 'button']
                ); ?>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-pencil"></span> Change',
                    ['update', 'id' => $model->id, '#' => 'tab_file'],
                    ['data-pjax' => 0, 'class' => 'btn btn-default', 'role' => 'button']
                ); ?>
            </p>
          </div>
        </div>
      </div>

    <?php } if (!empty($model->file)) { ?>

      <div class="col-sm-12 col-md-6">
        <div class="panel <?= ($model->file1Consistency ? 'panel-default' : 'panel-danger'); ?>">
          <div class="panel-heading">
            <span><?= $model->getattributeLabel('file')?></span>
          </div>
          <div class="panel-body">
            <p>
                <?= '<span class="glyphicon glyphicon-file"></span> ' . basename($model->file); ?>
            </p>
            <p>
                <span class="text-muted">Consistency: </span><?= ($model->file1Consistency ? 
                    '<span class="text-success">File valid</span>' :  
                    '<span class="not-set">File not valid</span>'
                ) ?><br>
                <span class="text-muted">Size: <?= yii::$app->formatter->format($model->fileSize, 'shortSize') ?></span><br>
                <span class="text-muted">MD5 Checksum: <?= yii::$app->formatter->format($model->md5, 'text') ?></span><br>
                <span class="text-muted"><?= yii::$app->formatter->format($model->fileInfo, 'html') ?></span>
            </p>
            <p>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-search"></span> Browse',
                    ['view', 'id' => $model->id, 'mode' => 'browse', 'type' => 'squashfs'],
                    [
                        'data-pjax' => 0,
                        'class' => [
                            'btn',
                            'btn-default',
                            $model->file1Consistency ? null : 'disabled',
                        ],
                        'role' => 'button'
                    ]
                ); ?>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-download-alt"></span> Download',
                    ['view', 'id' => $model->id, 'mode' => 'file', 'type' => 'squashfs'],
                    ['data-pjax' => 0, 'class' => 'btn btn-default', 'role' => 'button']
                ); ?>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-pencil"></span> Change',
                    ['update', 'id' => $model->id, '#' => 'tab_file'],
                    ['data-pjax' => 0, 'class' => 'btn btn-default', 'role' => 'button']
                ); ?>
            </p>
          </div>
        </div>
      </div>

    <?php } ?>

    </div>

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

