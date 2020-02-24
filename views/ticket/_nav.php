<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */

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

?>

<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#general">
        <i class="glyphicon glyphicon-home"></i>
        <?= \Yii::t('ticket', 'General') ?>
    </a></li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-comment"></i> ' . \Yii::t('ticket', 'Activity Log'),
            '#activities',
            ['data-toggle' => 'tab']
        ) ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-hdd"></i> ' . \Yii::t('ticket', 'Backups'),
            Url::to(['backup/index', 'ticket_id' => $model->id, '#' => 'backups']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-folder-open"></i> ' . \Yii::t('ticket', 'Browse Backup'),
            Url::to(['backup/browse', 'ticket_id' => $model->id, '#' => 'browse']),
            ['data-toggle' => 'tab', 'id' => 'browseButton']
        ); ?>
    </li>

    <?php if (Yii::$app->user->can('screenshot/view')) { ?>
        <li><a data-toggle="tab" href="#screenshots">
            <i class="glyphicon glyphicon-picture"></i>
            <?= \Yii::t('ticket', 'Screenshots') ?>
        </a></li>
    <?php } else { ?>
        <li class="disabled"><a class="disabled" data-toggle="" href="#">
            <i class="glyphicon glyphicon-picture"></i>
            <?= \Yii::t('ticket', 'Screenshots') ?>
        </a></li>
    <?php } ?>

    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-camera"></i> ' . \Yii::t('ticket', 'Screen Capture'),
            Url::to(['ticket/view', 'id' => $model->id, '#' => 'screencapture']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>

    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-sunglasses"></i> ' . \Yii::t('ticket', 'Result'),
            Url::to(['result/view', 'token' => $model->token, '#' => 'result']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>        
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-tasks"></i> ' . \Yii::t('ticket', 'Restores'),
            Url::to(['restore/index', 'ticket_id' => $model->id, '#' => 'restores']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-book"></i> ' . \Yii::t('ticket', 'History'),
            Url::to(['ticket/view', 'id' => $model->id, '#' => 'history']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="glyphicon glyphicon-list-alt"></i>
            <?= \Yii::t('ticket', 'Actions') ?>&nbsp;<span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-pencil"></span> ' . \Yii::t('ticket', 'Edit'),
                    ['update', 'id' => $model->id],
                    ['data-pjax' => 0]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-trash"></span> ' . \Yii::t('ticket', 'Delete'),
                    ['delete', 'id' => $model->id],
                    [
                        'data' => [
                            'confirm' => \Yii::t('ticket', 'Are you sure you want to delete this ticket?'),
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-save-file"></span> ' . \Yii::t('ticket', 'Generate PDF'),
                    ['view', 'mode' => 'report', 'id' => $model->id],
                    ['data-pjax' => 0]
                ) ?>
            </li>
            
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-hdd"></span> ' . \Yii::t('ticket', 'Backup Now'),
                    ['backup', 'id' => $model->id, '#' => 'backups'],
                    ['id' => 'backup-now']
                ) ?>
            </li>

            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-tasks"></span> ' . \Yii::t('ticket', 'Restore Desktop'),
                    Url::to([
                        'ticket/restore',
                        'id' => $model->id,
                        'file' => '::Desktop::',
                        'date' => 'now',
                    ]),
                    [
                        'data-href' => Url::to([
                            'ticket/restore',
                            'id' => $model->id,
                            'file' => '::Desktop::',
                            'date' => 'now',
                            '#' => 'tab_restores',
                        ]),
                        'data-toggle' => 'modal',
                        'data-target' => '#confirmRestore',
                        'data-path' => 'Desktop',
                        'data-version' => 'last backup',
                    ]
                ) ?>
            </li>

            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-tasks"></span> ' . \Yii::t('ticket', 'Restore Documents'),
                    Url::to([
                        'ticket/restore',
                        'id' => $model->id,
                        'file' => '::Documents::',
                        'date' => 'now',
                    ]),
                    [
                        'data-href' => Url::to([
                            'ticket/restore',
                            'id' => $model->id,
                            'file' => '::Documents::',
                            'date' => 'now',
                            '#' => 'tab_restores',
                        ]),
                        'data-toggle' => 'modal',
                        'data-target' => '#confirmRestore',
                        'data-path' => 'Documents',
                        'data-version' => 'last backup',
                    ]
                ) ?>
            </li>

            <?php if (Yii::$app->user->can('screenshot/snap')) { ?>
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-picture"></span> ' . \Yii::t('ticket', 'Get Live Screenshot'), [
                            'screenshot/snap',
                            'token' => $model->token,
                        ]
                    ) ?>
                </li>
            <?php } ?>

           <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-question-sign"></span> ' . \Yii::t('ticket', 'Visit the Manual'),
                    Url::to(['howto/view', 'id' => 'ticket-view.md']),
                    ['data-pjax' => 0]
                ); ?>
            </li>

            <?php if (YII_ENV_DEV) {
                echo '<li class="divider"></li>';
                echo '<li>';
                    echo Html::a(
                        '<span class="glyphicon glyphicon-file"></span> ' . \Yii::t('ticket', 'Download Exam'),
                        ['download', 'token' => $model->token],
                        ['data-pjax' => 0, 'class' => 'dev_item']
                    );
                echo '</li>';
                echo '<li>';
                    echo Html::a(
                        '<span class="glyphicon glyphicon-step-forward"></span> ' . \Yii::t('ticket', 'Finish Exam'),
                        ['finish', 'token' => $model->token],
                        ['data-pjax' => 0, 'class' => 'dev_item']
                    );
                echo '</li>';
                echo '<li>';
                    echo Html::a(
                        '<span class="glyphicon glyphicon-list-alt"></span> ' . \Yii::t('ticket', 'Get Exam Config'),
                        ['config', 'token' => $model->token],
                        ['data-pjax' => 0, 'class' => 'dev_item']
                    );
                echo '</li>';
            } ?>

        </ul>
    </li>

</ul>