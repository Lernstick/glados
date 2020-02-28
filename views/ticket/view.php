<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use yii\bootstrap\Modal;
use app\components\ActiveEventField;
use app\components\Editable;
use app\components\VideoJsWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $activitySearchModel app\models\ActivitySearch */
/* @var $activityDataProvider yii\data\ActiveDataProvider */
/* @var $backupSearchModel app\models\BackupSearch */
/* @var $backupDataProvider yii\data\ArrayDataProvider */
/* @var $screenshotSearchModel app\models\ScreenshotSearch */
/* @var $screenshotDataProvider yii\data\ArrayDataProvider */
/* @var $restoreSearchModel app\models\restoreSearch */
/* @var $restoreDataProvider yii\data\ActiveDataProvider */
/* @var $historySearchModel app\models\HistorySearch */
/* @var $historyDataProvider yii\data\ActiveDataProvider */
/* @var $date string the date */
/* @var $options array RdiffbackupFilesystem options array */

$this->title = \Yii::t('ticket', 'Ticket') . ' #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => \Yii::t('ticket', 'Tickets'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="ticket-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_nav', [
        'model' => $model,
    ]) ?>

    <?php Pjax::begin([
        'id' => 'backup-now-container',
        'linkSelector' => '#backup-now',
        'enablePushState' => false,
    ]); ?>
    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'restore-now-container',
        'linkSelector' => '#restore-now',
        'enablePushState' => false,
    ]); ?>
    <?php Pjax::end(); ?>

    <p></p>

    <?php ActiveEventField::begin([
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'action',
        'jsHandler' => 'function(d, s){if(d == "update"){$.pjax.reload({container: "#general"});}}'
    ]) ?>
    <?php ActiveEventField::end(); ?>

    <div class="tab-content">

    <?php Pjax::begin([
        'id' => 'general',
        'options' => ['class' => 'tab-pane fade in active'],
    ]); ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'createdAt:timeago',
            [
                'attribute' => 'token',
                'value' => Editable::widget([
                    'content' => yii::$app->formatter->format($model->token, 'text'),
                    'editUrl' => ['ticket/update', 'id' => $model->id, 'mode' => 'editable', 'attr' => 'token' ],
                ]),
                'format' => 'raw'
            ],
            [
                'attribute' => 'state',
                'value' => '<span class="label label-' . (
                    array_key_exists($model->state, $model->classMap) ? $model->classMap[$model->state] : 'default'
                ) . '">' . yii::$app->formatter->format($model->state, 'state') . '</span>',
                'format' => 'html',
            ],
            [
                'attribute' => 'exam.name',
                'format' => 'raw',
                'label' => \Yii::t('ticket', 'Exam'),
                'value' => Html::a(
                    $model->exam->subject  . ' - ' . $model->exam->name,
                    ['exam/view', 'id' => $model->exam->id],
                    ['data-pjax' => 0]
                )
            ],

            'start:timeago',
            'end:timeago',
            'duration:duration',
            [
                'attribute' => 'valid',
                'value' => '<span class="label label-'
                     . ( $model->valid ? 'success' : 'danger' )
                     . '">' . ($model->valid ? \Yii::t('ticket', 'Yes') : \Yii::t('ticket', 'No')) . '</span> '
                     . ( $model->validTime !== false ? ($model->validTime === true ? \Yii::t('ticket', 'No Time Limit') : \Yii::t('ticket', 'for {time} after start', [
                            'time' => yii::$app->formatter->format($model->validTime, 'duration')
                        ])) : '<span class="not-set">(' . \Yii::t('ticket', 'expired') . ')</span>' ),

                    
                'format' => 'html',
            ],
            [
                'attribute' => 'test_taker',
                'value' => Editable::widget([
                    'content' => empty($model->test_taker) ? '<span class="not-set">(' . \Yii::t('ticket', 'not set') . ')</span>' : yii::$app->formatter->format($model->test_taker, 'text'),
                    'editUrl' => ['ticket/update', 'id' => $model->id, 'mode' => 'editable', 'attr' => 'test_taker' ],
                ]),
                'format' => 'raw'
            ],
            [
                'attribute' => 'ip',
                'format' => 'raw',
                'value' => yii::$app->formatter->format($model->ip, 'text') . ' ' . 
                    ActiveEventField::widget([
                        'options' => [
                            'tag' => 'span',
                            'class' => 'label label-' . ( $model->online === 1 ? 'success' :
                                                       ( $model->online === 0 ? 'danger' : 
                                                                                'warning') )
                        ],
                        'content' => ( $model->online === 1 ? \Yii::t('ticket', 'Online') :
                                     ( $model->online === 0 ? \Yii::t('ticket', 'Offline') : 
                                                              \Yii::t('ticket', 'Unknown')) ),
                        'event' => 'ticket/' . $model->id,
                        'jsonSelector' => 'online',
                        'jsHandler' => 'function(d, s){
                            if(d == "1"){
                                s.innerHTML = "' . \Yii::t('ticket', 'Online') . '";
                                s.classList.add("label-success");
                                s.classList.remove("label-danger");
                                s.classList.remove("label-warning");
                            }else if(d == "0"){
                                s.innerHTML = "' . \Yii::t('ticket', 'Offline') . '";
                                s.classList.add("label-danger");
                                s.classList.remove("label-success");
                                s.classList.remove("label-warning");
                            }
                        }',
                    ]) . ' ' .
                    Html::a(\Yii::t('ticket', 'Probe'), ['view', 'id' => $model->id, 'mode' => 'probe']),
            ],
            [
                'attribute' => 'client_state',
                'format' => 'raw',
                'value' =>  ActiveEventField::widget([
                    'options' => [ 'tag' => 'span' ],
                    'content' => $model->client_state,
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'client_state',
                ]) . ' <div class="progress fade ' . ($model->download_lock == 1 ? 'in' : '') . '" style="display: inline-table; width:33.33%;">' . 
                    ActiveEventField::widget([
                        'content' => ActiveEventField::widget([
                            'options' => [ 'tag' => 'span' ],
                            'content' => yii::$app->formatter->format($model->download_progress, 'percent'),
                            'event' => 'ticket/' . $model->id,
                            'jsonSelector' => 'download_progress',
                            'jsHandler' => 'function(d, s){
                                s.innerHTML = d;
                                s.parentNode.style = "width:" + d;
                            }',
                        ]),
                        'event' => 'ticket/' . $model->id,
                        'jsonSelector' => 'download_lock',
                        'jsHandler' => 'function(d, s){
                            if(d == "1"){
                                s.classList.add("active");
                                s.parentNode.classList.add("in");
                            }else if(d == "0"){
                                s.classList.remove("active");
                                s.parentNode.classList.remove("in");
                            }
                        }',
                        'options' => [
                            'class' => 'progress-bar progress-bar-striped ' . ($model->download_lock == 1 ? 'active' : null),
                            'role' => '"progressbar',
                            'aria-valuenow' => '0',
                            'aria-valuemin' => '0',
                            'aria-valuemax' => '100',
                            'style' => 'width:' . yii::$app->formatter->format($model->download_progress, 'percent') . ';',
                        ]
                    ]) . 
                '</div>'
            ],
            [
                'attribute' => 'backup_state',
                'format' => 'raw',
                'value' =>  ActiveEventField::widget([
                    'options' => [
                        'tag' => 'i',
                        'class' => 'glyphicon glyphicon-cog ' . ($model->backup_lock == 1 ? 'gly-spin' : 'hidden'),
                        'style' => 'float: left;',
                    ],
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'backup_lock',
                    'jsHandler' => 'function(d, s){
                        if(d == "1"){
                            s.classList.add("gly-spin");
                            s.classList.remove("hidden");
                        }else if(d == "0"){
                            s.classList.remove("gly-spin");
                            s.classList.add("hidden");
                        }
                    }',
                ]) . ActiveEventField::widget([
                    'options' => [
                        'style' => 'float:left'
                    ],
                    'content' => yii::$app->formatter->format($model->backup_state, 'ntext'),
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'backup_state',
                ]) . ActiveEventField::widget([
                    'content' => yii::$app->formatter->format('&nbsp;<i class="glyphicon glyphicon-ok text-success"></i>&nbsp;' . \Yii::t('ticket', 'last backup successful'), 'html'),
                    'options' => [
                        'class' => $model->last_backup == 1 ? '' : 'hidden'
                    ],
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'last_backup',
                    'jsHandler' => 'function(d, s){
                        if(d == "1"){
                            s.classList.remove("hidden");
                        }else if(d == "0"){
                            s.classList.add("hidden");
                        }
                    }',
                ]) . yii::$app->formatter->format('<div style="float:left;" class="' . ($model->lastBackupFailed ? '' : 'hidden') . '">&nbsp;<i class="glyphicon glyphicon-remove text-danger"></i>&nbsp;' . \Yii::t('ticket', 'last backup failed') . '</div>', 'html')
                . ($model->abandoned ? ('&nbsp;<a tabindex="0" class="label label-danger" role="button" data-toggle="popover" data-html="true" data-trigger="focus" title="' . \Yii::t('ticket', 'Abandoned Ticket') . '" data-content="' . \Yii::t('ticket', 'This ticket is abandoned and thus excluded from regular backup. A reason for this could be that the backup process was not able to perform a backup of the client. After some time of failed backup attempts, the ticket will be abandoned (the value of <i>Time Limit</i> of this ticket/exam or <i>{default}</i> if nothing is set). You can still force a backup by clicking Actions->Backup Now.', ['default' => yii::$app->formatter->format(\Yii::$app->params['abandonTicket'], 'duration')]) . '">' . \Yii::t('ticket', 'Abandoned') . '</a>') : ''),
            ],
            [
                'attribute' => 'restore_state',
                'format' => 'raw',
                'value' =>  ActiveEventField::widget([
                    'options' => [
                        'tag' => 'i',
                        'class' => 'glyphicon glyphicon-cog ' . ($model->restore_lock == 1 ? 'gly-spin' : 'hidden'),
                        'style' => 'float: left;',
                    ],
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'restore_lock',
                    'jsHandler' => 'function(d, s){
                        if(d == "1"){
                            s.classList.add("gly-spin");
                            s.classList.remove("hidden");
                        }else if(d == "0"){
                            s.classList.remove("gly-spin");
                            s.classList.add("hidden");
                        }
                    }',
                ]) . ActiveEventField::widget([
                    'content' => yii::$app->formatter->format($model->restore_state, 'ntext'),
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'restore_state',
                ]),
            ],         
            [
                'attribute' => 'bootup_lock',
                'format' => 'raw',
                'value' =>  ActiveEventField::widget([
                    'content' => yii::$app->formatter->format($model->bootup_lock, 'ntext'),
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'bootup_lock',
                ]),
                'visible' => YII_ENV_DEV,
                'captionOptions' => ['class' => 'dev_item']
            ],           
        ],

    ]) ?>

    <?= $this->render('@app/views/_notification') ?>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'activities',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>
        <?php $_GET = array_merge($_GET, ['#' => 'tab_activities']); ?>
        <?= $this->render('/activity/_item', [
            'searchModel' => $activitySearchModel,
            'dataProvider' => $activityDataProvider,
            'ticket' => $model,
        ]); ?>
    <?php Pjax::end(); ?>

    <?php ActiveEventField::begin([
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'backup_lock',
        'jsHandler' => 'function(d, s){if(d == "0"){
            $.pjax.reload({container: "#backups", async:false});
            $.pjax.reload({container: "#screenshots", async:false});
        }}'
    ]) ?>
    <?php ActiveEventField::end(); ?>

    <?php ActiveEventField::begin([
        'event' => 'ticket/' . $model->id,
        'jsonSelector' => 'restore_lock',
        'jsHandler' => 'function(d, s){if(d == "0"){
            $.pjax.reload({container: "#restores", async:false});
        }}'
    ]) ?>
    <?php ActiveEventField::end(); ?>


    <?php Pjax::begin([
        'id' => 'backups',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>
        <?php $_GET = array_merge($_GET, ['#' => 'tab_backups']); ?>
        <?= $this->render('/backup/index', [
            'ticketModel' => $model,
            'searchModel' => $backupSearchModel,
            'dataProvider' => $backupDataProvider,
        ]); ?>
    <?php Pjax::end() ?>

    <?php Pjax::begin([
        'id' => 'screenshots',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

    <?php if (Yii::$app->user->can('screenshot/view')) { ?>

        <?php $_GET = array_merge($_GET, ['#' => 'tab_screenshots']); ?>
        <?= ListView::widget([
            'dataProvider' => $screenshotDataProvider,
            'options' => ['class' => 'row'],
            'itemOptions' => ['class' => 'col-xs-6 col-md-3'],
            'itemView' => function ($model, $key, $index, $widget) {
                return '<div class="thumbnail"><a data-pjax="0" href="' . $model->src . '">'
                     . '<img src="' . $model->tsrc . '" title="' . $model->date . '"></a>'
                     . '<div class="caption">' . yii::$app->formatter->format($model->date, 'timeago') . '</div>'
                     . '</div>';
            },
            'summaryOptions' => [
                'class' => 'summary col-xs-12 col-md-12',
            ],            
            'emptyText' => \Yii::t('ticket', 'No screenshots found.'),
            'layout' => '{items} <br>{summary} {pager}',
        ]); ?>

    <?php Pjax::end() ?>
    <?php } ?>

    <?php Pjax::begin([
        'id' => 'screencapture',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

        <button onClick="var player = videojs('videojs-w4'); player.play();">init</button>
        <?= VideoJsWidget::widget([
            'options' => [
                'class' => 'video-js vjs-default-skin vjs-big-play-centered',
                'poster' => "http://www.videojs.com/img/poster.jpg",
                'controls' => true,
                'preload' => 'auto',
                'width' => '970',
                'height' => '400',
            ],
            'tags' => [
                'source' => [
                    [
                        'src' => 'https://d2zihajmogu5jn.cloudfront.net/bipbop-advanced/bipbop_16x9_variant.m3u8',
                        'type' => 'application/x-mpegURL',
                    ],
                ],
            ]
        ]); ?>

    <?php Pjax::end() ?>

    <?php Pjax::begin([
        'id' => 'browse',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>

        <?php $_GET = array_merge($_GET, ['#' => 'tab_browse']); ?>
        <?= $this->render('/backup/browse', [
            'ItemsDataProvider' => $ItemsDataProvider,
            'VersionsDataProvider' => $VersionsDataProvider,
            'ticket' => $model,
            'fs' => $fs,
            'date' => $date,
            'options' => $options,
        ]); ?>

    <?php Pjax::end() ?>

    <?php Pjax::begin([
        'id' => 'restores',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>
        <?php $_GET = array_merge($_GET, ['#' => 'tab_restores']); ?>
        <?= $this->render('/restore/index', [
            'ticketModel' => $model,
            'searchModel' => $restoreSearchModel,
            'dataProvider' => $restoreDataProvider,
        ]); ?>
    <?php Pjax::end() ?>

    <?php Pjax::begin([
        'id' => 'result',
        'options' => ['class' => 'tab-pane fade'],
    ]); ?>
        <?php $_GET = array_merge($_GET, ['#' => 'tab_result']); ?>
        <?= $this->render('/result/_view', [
            'model' => $model,
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

</div>

<?php Modal::begin([
    'id' => 'confirmRestore',
    'header' => '<h4>' . \Yii::t('ticket', 'Confirm Restore') . '</h4>',
    'footer' => Html::Button(\Yii::t('ticket', 'Cancel'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']) . '<a id="restore-now" class="btn btn-danger btn-ok">' . \Yii::t('ticket', 'Restore') . '</a>',
    //'size' => \yii\bootstrap\Modal::SIZE_SMALL
]); ?>

<p><?= \Yii::t('ticket', "You're about to restore:") ?></p>
<div class="list-group">
  <li class="list-group-item">
    <h4 id='confirmRestoreItemPath' class="list-group-item-heading">/path/to/file</h4>
    <p class="list-group-item-text"><?= \Yii::t('ticket', "to the state as it was at <b id='confirmRestoreItemDate'>date</b>") ?></p>
  </li>
</div>

<div class="alert alert-danger" role="alert">
  <h4><?= \Yii::t('ticket', 'Important!') ?></h4>

  <p><?= \Yii::t('ticket', 'Please notice, that if the <b>file</b> exists on the target machine, it will be permanently <b>OVERWRITTEN</b> by this version!') ?></p>
  <p><?= \Yii::t('ticket', 'If you restore a <b>directory</b>, notice that the target directory will be restored to the exact same state of this version. Newer files will be <b>REMOVED</b>!') ?></p>
</div>

<?php Modal::end(); ?>