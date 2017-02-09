<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use yii\bootstrap\Modal;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $session yii\web\Session */
/* @var $activitySearchModel app\models\ActivitySearch */
/* @var $activityDataProvider yii\data\ActiveDataProvider */
/* @var $backupSearchModel app\models\BackupSearch */
/* @var $backupDataProvider yii\data\ArrayDataProvider */
/* @var $screenshotSearchModel app\models\ScreenshotSearch */
/* @var $screenshotDataProvider yii\data\ArrayDataProvider */
/* @var $restoreSearchModel app\models\ScreenshotSearch */
/* @var $restoreDataProvider yii\data\ActiveDataProvider */


$active_tabs = <<<JS
// Change hash for page-reload
$('.nav-tabs a').on('shown.bs.tab', function (e) {
    var prefix = "tab_";
    window.location.hash = e.target.hash.replace("#", "#" + prefix);
});

//$('.nav-tabs a[id="browseButton"]').on('shown.bs.tab', function (e) {
//    $.pjax({url: this.href, container: '#browse', push: false});
//});

// Javascript to enable link to tab
$(window).bind('hashchange', function() {
    var prefix = "tab_";
    $('.nav-tabs a[href*="' + document.location.hash.replace(prefix, "") + '"]').tab('show');
}).trigger('hashchange');
JS;
$this->registerJs($active_tabs);

$this->title = 'Ticket #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Tickets', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="ticket-view container">

    <h1><?= Html::encode($this->title) ?></h1>

    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#general">
            <i class="glyphicon glyphicon-home"></i>
            General
        </a></li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-comment"></i> Activity Log',
                '#activities',
                ['data-toggle' => 'tab']
            ) ?>
        </li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-hdd"></i> Backups',
                Url::to(['backup/index', 'ticket_id' => $model->id, '#' => 'backups']),
                ['data-toggle' => 'tab']
            ); ?>
        </li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-folder-open"></i> Browse Backup',
                Url::to(['backup/browse', 'ticket_id' => $model->id, '#' => 'browse']),
                ['data-toggle' => 'tab', 'id' => 'browseButton']
            ); ?>
        </li>
        <li><a data-toggle="tab" href="#screenshots">
            <i class="glyphicon glyphicon-picture"></i>
            Screenshots
        </a></li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-tasks"></i> Restores',
                Url::to(['restore/index', 'ticket_id' => $model->id, '#' => 'restores']),
                ['data-toggle' => 'tab']
            ); ?>
        </li>
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="glyphicon glyphicon-list-alt"></i>
                Actions<span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-pencil"></span> Update',
                        ['update', 'id' => $model->id],
                        ['data-pjax' => 0]
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-trash"></span> Delete',
                        ['delete', 'id' => $model->id],
                        [
                            'data' => [
                                'confirm' => 'Are you sure you want to delete this item?',
                                'method' => 'post',
                            ],
                        ]
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-save-file"></span> Generate PDF',
                        ['view', 'mode' => 'report', 'id' => $model->id],
                        ['data-pjax' => 0]
                    ) ?>
                </li>
                
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-hdd"></span> Backup Now',
                        ['backup', 'id' => $model->id],
                        ['id' => 'backup-now']
                    ) ?>
                </li>

                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-tasks"></span> Restore Desktop', [
                            'restore',
                            'id' => $model->id,
                            //'date' => '2016-06-01T12:44:33+02:00',
                            'date' => 'now',
                            'file' => '::Desktop::',
                        ],
                        ['id' => 'restore-now']
                    ) ?>
                </li>

                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-tasks"></span> Restore Documents', [
                            'restore',
                            'id' => $model->id,
                            'date' => 'now',
                            'file' => '::Documents::',
                        ],
                        ['id' => 'restore-now']
                    ) ?>
                </li>

                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-picture"></span> Get Live Screenshot', [
                            'screenshot/snap',
                            'token' => $model->token,
                        ]
                    ) ?>
                </li>

                <li class="divider"></li> 
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-file"></span> Download Exam',
                        ['download', 'token' => $model->token],
                        ['data-pjax' => 0]
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-step-forward"></span> Finish Exam',
                        ['finish', 'token' => $model->token],
                        ['data-pjax' => 0]
                    ) ?>
                </li>
            </ul>            
        </li>

    </ul>

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
            'token',
            [
                'attribute' => 'state',
                'value' => '<span class="label label-' . (
                    array_key_exists($model->state, $model->classMap) ? $model->classMap[$model->state] : 'default'
                ) . '">' . yii::$app->formatter->format($model->state, 'state') . '</span>',
                'format' => 'html',
            ],
            'exam.subject',
            [
                'attribute' => 'exam.name',
                'format' => 'raw',
                'value' => Html::a(
                    $model->exam->name,
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
                     . '">' . ($model->valid ? 'Yes' : 'No') . '</span> '
                     . ( $model->validTime ? 'for ' . yii::$app->formatter->format($model->validTime, 'duration') : '<span class="not-set">(expired)</span>' ),
                'format' => 'html',
            ],
            'test_taker',
            [
                'attribute' => 'ip',
                'format' => 'raw',
                'value' => yii::$app->formatter->format($model->ip, 'text') . " " . 
                    ( $online == 0 ?    '<span class="label label-success">Online</span>' :
                    ( $online == -1 ?   '<span class="label label-warning">Unknown</span>' : 
                                        '<span class="label label-danger">Offline</span>') ) . " " .
                    Html::a('Probe', ['view', 'id' => $model->id, 'mode' => 'probe']),
            ],
            [
                'attribute' => 'client_state',
                'format' => 'raw',
                'value' =>  ActiveEventField::widget([
                    'content' => $model->client_state,
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'client_state',
                ]),
            ],

            [
                'attribute' => 'download_progress', 
                'format' => 'raw',
                'value' => '<div class="progress">' . 
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
                            }else if(d == "0"){
                                s.classList.remove("active");
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
                    'content' => yii::$app->formatter->format($model->backup_state, 'ntext'),
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'backup_state',
                ]),
            ],
            [
                'attribute' => 'restore_state',
                'format' => 'raw',
                'value' =>  ActiveEventField::widget([
                    'content' => yii::$app->formatter->format($model->restore_state, 'ntext'),
                    'event' => 'ticket/' . $model->id,
                    'jsonSelector' => 'restore_state',
                ]),
            ],
            'bootup_lock',            
        ],

    ]) ?>

    <?= $this->render('@app/views/_notification', [
        'session' => $session,
    ]) ?>

    <?php Pjax::end(); ?>

    <div id="activities" class="tab-pane fade">

        <?php $_GET = array_merge($_GET, ['#' => 'tab_activities']); ?>
        <?= $this->render('/activity/_item', [
            'searchModel' => $activitySearchModel,
            'dataProvider' => $activityDataProvider,
        ]); ?>

    </div>

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
            'emptyText' => 'No screenshots found.',
            'layout' => '{items} <br>{summary} {pager}',
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

</div>

<?php

Modal::begin([
    'id' => 'logModal',
    'header' => '<h4>Backup Log</h4>',
    'footer' => Html::Button('Close', ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]);

    Pjax::begin([
        'id' => 'logModalContent',
    ]);
    Pjax::end();

Modal::end();