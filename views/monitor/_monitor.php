<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap\Modal;
use yii\helpers\Url;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $exam app\models\Exam */
/* @var $searchModel app\models\TicketSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $issueSearchModel app\models\IssueSearch */
/* @var $issueDataProvider yii\data\ActiveDataProvider */

$title = \Yii::t('ticket', 'Currently behind live');
$n = $exam::monitor_idle_time();

// reload images if no new image for [[monitor_idle_time()]] seconds
$js = <<< SCRIPT
function check_images() {
    $('img.live-thumbnail').each(function() {
        var img = $(this);
        var src = img.attr('data-url');
        var then = parseFloat(img.attr("data-time"));
        var now = parseFloat(new Date().getTime()/1000);
        if (now - then > $n) {
            img.attr('src', src + '?_ts=' + parseInt(now));
            img.attr("data-time", now);
            img.siblings("a").find(".live-indicator").removeClass("live");
            img.siblings("a").find(".live-indicator").attr("title", "$title");
        }
    });
}
setInterval(check_images,1000);

// ensures this works for some older browsers
MutationObserver = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver;

$('img.live-thumbnail').each(function(){
    var img = this;
    new MutationObserver(function() {
        $("<img/>").on('load', function() {
            // succesful loaded image
            $(img).show();
            $(img).next("div").hide();
        }).on('error', function() {
            // error loading image
            $(img).hide();
            $(img).next("div").show();
        }).attr("src", $(img).attr("src"));
    }).observe(img, {
        attributes:true,
        attributeFilter:["src"]
    });
});

check_images();

$('#galleryModal').off('show.bs.modal').on('show.bs.modal', function(e) {
    var el = $(e.relatedTarget).parent().children('img').first();
    $.pjax({url: el.data('src'), container: '#monitorModalContent', push: false, async:false})
});

$('#galleryModal').off('hide.bs.modal').on('hide.bs.modal', function(e) {
    $("#reload").click();
});

function check_load() {
    $("#reload-load").click();
}
setInterval(check_load,10000);

SCRIPT;

echo ActiveEventField::widget([
    'event' => 'exam/' . $exam->id,
    'jsonSelector' => 'runningTickets',
    'jsHandler' => 'function(d, s) {
        $("#reload").click();
    }',
]);

echo ActiveEventField::widget([
    'event' => 'exam/' . $exam->id,
    'jsonSelector' => 'newIssue',
    'jsHandler' => 'function(d, s) {
        console.log("newIssue", d, s);
        $("#reload-issues").click();
    }',
]);

echo Pjax::widget([
    'id' => 'backup-now-container',
    'linkSelector' => '#backup-now',
    'enablePushState' => false,
    'options' => [
        'class' => 'hidden'
    ],
]);

Pjax::begin([
    'id' => 'live_issues',
]);

    /**
     * A dummy event in the group "issues", such that if no event in the group "issues" is active
     * the new js event handlers won't be registered. Also they won't be unregistered if the last
     * element is disappearing from the view. Using this, an event of type "issues" will always be 
     * present in the view.
     */
    echo ActiveEventField::widget([
        'event' => 'issues:exam/' . $exam->id,
        'jsonSelector' => 'dummy',
        'jsHandler' => 'function(d, s){}', // do nothing
    ]);

    echo GridView::widget([
        'dataProvider' => $issueDataProvider,
        'columns' => [
            [
                'attribute' => 'key',
                'format' => 'issue',
                'headerOptions' => ['class' => 'col-md-2'],
            ],
            /*'key:issue',*/
            /*'occuredAt:timeago',*/
            [
                'attribute' => 'occuredAt',
                'format' => 'timeago',
                'headerOptions' => ['class' => 'col-md-2'],
            ],
            [
                'attribute' => 'ticket.token',
                'headerOptions' => ['class' => 'col-md-1'],
                'format' => 'raw',
                'value' => function($issue, $key, $index, $datacolumn) {
                    return Html::a($issue->ticket->token,
                        ['ticket/view', 'id' => $issue->ticket->id],
                        ['data-pjax' => 0]
                    );
                },
            ],
            [
                'attribute' => 'Info',
                'format' => 'links',
                /**
                 * @param $issue Issue model instance
                 * @param $key
                 * @param $index
                 * @param $grid yii\grid\DataColumn current DataColumn instance
                 */
                'value' => function($issue, $key, $index, $datacolumn) {
                    if ($issue->key == $issue::CLIENT_OFFLINE) {
                        $value = ActiveEventField::widget([
                            'options' => [ 'tag' => 'span' ],
                            'content' => $issue->ticket->client_state,
                            'event' => 'issues:ticket/' . $issue->ticket->id,
                            'jsonSelector' => 'client_state',
                        ]);
                    } else if ($issue->key == $issue::LONG_TIME_NO_BACKUP) {
                        $value = $datacolumn->grid->render('/ticket/fields/_backup_state', [
                            'model' => $issue->ticket,
                            'group' => 'issues',
                        ]);
                    } else {
                        $value = '';
                    }
                    return $value;
                },
            ],
            [
                'class' => yii\grid\ActionColumn::className(),
                'header' => \Yii::t('ticket', 'Actions') . Html::a('<i class="glyphicon glyphicon-refresh"></i>', '', ['id' => 'reload-issues', 'class' => 'btn btn-default btn-xs pull-right']),
                'headerOptions' => ['class' => 'col-md-1'],
                'template' => '{action}',
                'buttons' => [
                    'action' => function ($url, $issue, $key) {
                        if ($issue->key == $issue::CLIENT_OFFLINE) {
                            return Html::a('<i class="glyphicon glyphicon-globe"></i>&nbsp;' . Yii::t('issue', 'Ping client'), [
                                '/ticket/ping',
                                'id' => $issue->ticket->id,
                                '#' => 'tab_general'
                            ], [
                                'id' => 'backup-now',
                                'class' => 'btn btn-default',
                            ]);
                        } else if ($issue->key == $issue::LONG_TIME_NO_BACKUP) {
                            return Html::a('<i class="glyphicon glyphicon-hdd"></i>&nbsp;' . Yii::t('issue', 'Backup now'), [
                                '/ticket/backup',
                                'id' => $issue->ticket->id,
                                '#' => 'tab_backups'
                            ], [
                                'id' => 'backup-now',
                                'class' => 'btn btn-default'
                            ]);
                        } else {
                            return $issue->key;
                        }
                    },
                ],
            ],
        ],
        'rowOptions' => ['class' => 'danger '],
        'emptyText' => '<i class="glyphicon glyphicon-ok"></i>&nbsp;' . \Yii::t('ticket', 'Everything is fine. No issues found.'),
        'emptyTextOptions' => ['class' => 'empty text-success '],
    ]);

Pjax::end();



echo "<hr>";

Pjax::begin([
    'id' => 'live_monitor'
]);

    /**
     * A dummy event in the group "monitor", such that if no event in the group "monitor" is active
     * the new js event handlers won't be registered. Also they won't be unregistered if the last
     * element is disappearing from the view. Using this, an event of type "monitor" will always be 
     * present in the view.
     */
    echo ActiveEventField::widget([
        'event' => 'monitor:exam/' . $exam->id,
        'jsonSelector' => 'dummy',
        'jsHandler' => 'function(d, s){}', // do nothing
    ]);

    ?>

    <div class="row">
        <div class="col-sm-9">
            <span><?= \Yii::t('monitor', 'Only tickets in {state} state will be shown here.', [
                'state' => '<span data-state="1" class="label view--state">' . Yii::t('ticket', 'Running') . '</span>'
            ]); ?></span>
        </div>
        <div class="col-sm-3 text-right">
            <!-- Don't remove this button! Make it invisible instead. -->
            <a class="btn btn-default" id="reload" href=""><i class="glyphicon glyphicon-refresh"></i>&nbsp;<?= Yii::t('app', 'Reload') ?></a>
        </div>
    </div>
    
    <?php $this->registerJs($js, \yii\web\View::POS_READY); ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'options' => ['class' => 'row'],
        'itemOptions' => ['class' => 'col-xs-6 col-md-3 live-overview-item-container'],
        'itemView' => '_live_overview_item',
        'summaryOptions' => [
            'class' => 'summary col-xs-12 col-md-12',
        ],
        'emptyText' => \Yii::t('ticket', 'No running exams found.'),
        'emptyTextOptions' => ['class' => 'col-md-12 text-center'],
        'layout' => '{pager} {summary}<br>{items}',
    ]); ?>

<?php Pjax::end(); ?>

<?php Modal::begin([
    'id' => 'galleryModal',
    'header' => false,
    'footer' => Html::Button(\Yii::t('app', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]);

    Pjax::begin([
        'id' => 'monitorModalContent',
    ]);
    Pjax::end();

Modal::end(); ?>