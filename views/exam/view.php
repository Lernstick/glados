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
/* @var $historySearchModel app\models\HistorySearch */
/* @var $historyDataProvider yii\data\ActiveDataProvider */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \Yii::t('exams', 'Exams'), 'url' => ['index']];
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
                    'label' => \Yii::t('exams', 'Owner'),
                    'value' => ( $model->user_id == null ? '<span class="not-set">(' . \Yii::t('exams', 'user removed') . ')</span>' : '<span>' . $model->user->username . '</span>' ),
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
              <span><?= \Yii::t('exams', 'Exam file') ?></span>
            </div>
            <div class="panel-body">
              <p>
                <span class="not-set"><?= \Yii::t('exams', 'No file found') ?></span>
              </p>
              <p class="text-muted">
                <span><?= \Yii::t('exams', 'You have to provide at least one exam file.') ?></span><br>
                <span class="glyphicon glyphicon-alert"></span>
                <span><?= \Yii::t('exams', 'For more information, please visit ') ?><?= Html::a(\Yii::t('exams', 'Manual / Create an exam'), ['/howto/view', 'id' => 'create-exam.md'], ['class' => 'alert-link']) ?>.</span>
              </p>
              <p>
                  <?= Html::a(
                    '<span class="glyphicon glyphicon-plus"></span> ' . \Yii::t('exams', 'Add file ...'),
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
                <span class="text-muted"><?= \Yii::t('exams', 'Consistency') ?>: </span><?= ($model->file2Consistency ? 
                    '<span class="text-success">' . \Yii::t('exams', 'File valid') . '</span>' :  
                    '<strong><span class="not-set">' . \Yii::t('exams', 'File not valid') . '</span></strong>'
                ) ?><br>
                <span class="text-muted"><?= \Yii::t('exams', 'Size') ?>: <?= yii::$app->formatter->format($model->file2Size, 'shortSize') ?></span><br>
                <span class="text-muted"><?= yii::$app->formatter->format($model->file2Info, 'html') ?></span>
            </p>
            <p>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-search"></span> ' . \Yii::t('exams', 'Browse'),
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
                    '<span class="glyphicon glyphicon-download-alt"></span> ' . \Yii::t('exams', 'Download'),
                    ['view', 'id' => $model->id, 'mode' => 'file', 'type' => 'zip'],
                    ['data-pjax' => 0, 'class' => 'btn btn-default', 'role' => 'button']
                ); ?>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-pencil"></span> ' . \Yii::t('exams', 'Change'),
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
                <span class="text-muted"><?= \Yii::t('exams', 'Consistency') ?>: </span><?= ($model->file1Consistency ? 
                    '<span class="text-success">' . \Yii::t('exams', 'File valid') . '</span>' :  
                    '<span class="not-set">' . \Yii::t('exams', 'File not valid') . '</span>'
                ) ?><br>
                <span class="text-muted"><?= \Yii::t('exams', 'Size') ?>: <?= yii::$app->formatter->format($model->fileSize, 'shortSize') ?></span><br>
                <span class="text-muted"><?= \Yii::t('exams', 'MD5 Checksum') ?>: <?= yii::$app->formatter->format($model->md5, 'text') ?></span><br>
                <span class="text-muted"><?= yii::$app->formatter->format($model->fileInfo, 'html') ?></span>
            </p>
            <p>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-search"></span> ' . \Yii::t('exams', 'Browse'),
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
                    '<span class="glyphicon glyphicon-download-alt"></span> ' . \Yii::t('exams', 'Download'),
                    ['view', 'id' => $model->id, 'mode' => 'file', 'type' => 'squashfs'],
                    ['data-pjax' => 0, 'class' => 'btn btn-default', 'role' => 'button']
                ); ?>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-pencil"></span> ' . \Yii::t('exams', 'Change'),
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

    <div class="alert alert-warning" role="alert"><i class="glyphicon glyphicon-warning-sign"></i> <?= \Yii::t('exams', 'Please notice, all these settings will <b>override</b> the settings configured in the exam file!') ?></div>

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
                'label' => \Yii::t('exams', 'Screenshot Interval'),
                'value' => $model->screenshots_interval*60, # in seconds
                'format' => 'duration'
            ],
            [
                'label' => \Yii::t('exams', 'Maximum brightness'),
                'value' => $model->max_brightness/100,
                'format' => 'percent'
            ],            
        ],
    ]) ?>

    <?= DetailView::widget([
        'model' => $model,
        'template' => '<tr><th{captionOptions}>{value}</th><td{contentOptions}>{label}</td></tr>',
        'attributes' => [
            [
                'label' => \Yii::t('exams', 'Libreoffice: Save AutoRecovery information (to <code>{path}</code> every <code>{interval}</code>)', [
                    'path' => yii::$app->formatter->format($model->libre_autosave_path, 'text'),
                    'interval' => yii::$app->formatter->format($model->libre_autosave_interval*60, 'duration'),
                ]),
                'value' => $model->libre_autosave,
                'format' => 'boolean'
            ],
            [
                'label' => \Yii::t('exams', 'Libreoffice: Always create backup copy (to <code>{path}</code>)', [
                    'path' => yii::$app->formatter->format($model->libre_createbackup_path, 'text'),
                ]),
                'value' => $model->libre_createbackup,
                'format' => 'boolean'
            ],
        ],
    ]) ?>

    <?= ListView::widget([
        'dataProvider' => $urlWhitelistDataProvider,
        #'options' => [ 'tag' => 'table', 'class' => 'table table-bordered table-hover'],
        'itemOptions' => [ 'tag' => 'tr' ],
        'itemView' => function ($model, $key, $index, $widget) {
            return '<td>' . $model . '</td>';
        },
        'emptyText' => '<table class="table table-bordered table-hover"><tr><th>HTTP URL Whitelist</th></tr><tr><td>' . \Yii::t('exams', 'No URLs found.') . '</td></tr></table>',
        'layout' => '<table class="table table-bordered table-hover"><tr><th>' . \Yii::t('exams', 'HTTP URL Whitelist') . '</th></tr>{items}</table> {pager} {summary}',
    ]); ?>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'monitor',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>
    <?php Pjax::end() ?>

    <?php Pjax::begin([
        'id' => 'history',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>
        <?php $_GET = array_merge($_GET, ['#' => 'tab_history']); ?>
        <?= $this->render('/history/index', [
            'model' => $model,
            'searchModel' => $historySearchModel,
            'dataProvider' => $historyDataProvider,
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
                    'text' => \Yii::t('exams', 'Tickets for Exam "{exam}" in "{subject}"', [
                        'exam' => $model->name,
                        'subject' => $model->subject
                    ])
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
                        'name' => \Yii::t('exams', 'Tickets'),
                        'colorByPoint' => true,
                        'data' => [
                            [
                                'name' => \Yii::t('exams', 'Open'),
                                'y' => $model->openTicketCount,
                                'color' => '#5cb85c'
                            ],
                            [
                                'name' => \Yii::t('exams', 'Running'),
                                'y' => $model->runningTicketCount,
                                'color' => '#286090',
                                'sliced' => true,
                                'selected' => true
                            ],
                            [
                                'name' => \Yii::t('exams', 'Closed'),
                                'y' => $model->closedTicketCount,
                                'color' => '#d9534f'
                            ],
                            [
                                'name' => \Yii::t('exams', 'Submitted'),
                                'y' => $model->submittedTicketCount,
                                'color' => '#f0ad4e'
                            ],
                            [
                                'name' => \Yii::t('exams', 'Unknown'),
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

