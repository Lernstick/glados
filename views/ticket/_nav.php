<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\ActiveEventField;

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

<ul class="nav nav-tabs nav-special">
    <li class="active"  title="<?= \Yii::t('ticket', 'General') ?>"><a data-toggle="tab" href="#general">
        <div><i class="glyphicon glyphicon-home"></i>
        <span><?= \Yii::t('ticket', 'General') ?></span></div>
    </a></li>
    <li title="<?= \Yii::t('ticket', 'Activity Log') ?>">
        <?= Html::a(
            '<div><i class="glyphicon glyphicon-comment"></i> <span>' . \Yii::t('ticket', 'Activity Log') . '</span></div>',
            '#activities',
            ['data-toggle' => 'tab']
        ) ?>
    </li>
    <li title="<?= \Yii::t('ticket', 'Backups') ?>">
        <?= Html::a(
            '<div>'
            . ActiveEventField::widget([
                'options' => [
                    'tag' => 'i',
                    'class' => 'glyphicon ' . ($model->backup_lock == 1 ? 'glyphicon-cog gly-spin text-danger' : 'glyphicon-hdd'),
                ],
                'event' => 'ticket/' . $model->id,
                'jsonSelector' => 'backup_lock',
                'jsHandler' => 'function(d, s){
                    if(d == "1"){
                        s.classList.remove("glyphicon-hdd");
                        s.classList.add("gly-spin");
                        s.classList.add("glyphicon-cog");
                        s.classList.add("text-danger");
                    }else if(d == "0"){
                        s.classList.remove("gly-spin");
                        s.classList.remove("text-danger");
                        s.classList.remove("glyphicon-cog");
                        s.classList.add("glyphicon-hdd");
                    }
                }',
            ]) . ' <span>' . \Yii::t('ticket', 'Backups') . '</span></div>',
            Url::to(['backup/index', 'ticket_id' => $model->id, '#' => 'backups']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>
    <li title="<?= \Yii::t('ticket', 'Browse Backup') ?>">
        <?= Html::a(
            '<div><i class="glyphicon glyphicon-folder-open"></i> <span>' . \Yii::t('ticket', 'Browse Backup') . '</span></div>',
            Url::to(['backup/browse', 'ticket_id' => $model->id, '#' => 'browse']),
            ['data-toggle' => 'tab', 'id' => 'browseButton']
        ); ?>
    </li>

    <?php if (Yii::$app->user->can('screenshot/view')) { ?>
        <li title="<?= \Yii::t('ticket', 'Screenshots') ?>"><a data-toggle="tab" href="#screenshots">
            <div><i class="glyphicon glyphicon-picture"></i>
            <span><?= \Yii::t('ticket', 'Screenshots') ?></span></div>
        </a></li>
    <?php } else { ?>
        <li class="disabled" title="<?= \Yii::t('ticket', 'Screenshots') ?>"><a class="disabled" data-toggle="" href="#">
            <div><i class="glyphicon glyphicon-picture"></i>
            <span><?= \Yii::t('ticket', 'Screenshots') ?></span></div>
        </a></li>
    <?php } ?>

    <li title="<?= \Yii::t('ticket', 'Screen Capture') ?>">
        <?= Html::a(
            '<div><i class="glyphicon glyphicon-camera"></i> <span>' . \Yii::t('ticket', 'Screen Capture') . '</span></div>',
            Url::to(['ticket/view', 'id' => $model->id, '#' => 'screencapture']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>

    <li title="<?= \Yii::t('ticket', 'Result') ?>">
        <?= Html::a(
            '<div><i class="glyphicon glyphicon-sunglasses"></i> <span>' . \Yii::t('ticket', 'Result') . '</span></div>',
            Url::to(['result/view', 'token' => $model->token, '#' => 'result']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>        
    <li title="<?= \Yii::t('ticket', 'Restores') ?>">
        <?= Html::a(
            '<div>'
            . ActiveEventField::widget([
                'options' => [
                    'tag' => 'i',
                    'class' => 'glyphicon ' . ($model->restore_lock == 1 ? 'glyphicon-cog gly-spin' : 'glyphicon-tasks'),
                ],
                'event' => 'ticket/' . $model->id,
                'jsonSelector' => 'restore_lock',
                'jsHandler' => 'function(d, s){
                    if(d == "1"){
                        s.classList.add("gly-spin");
                        s.classList.add("glyphicon-cog");
                        s.classList.remove("glyphicon-tasks");
                    }else if(d == "0"){
                        s.classList.remove("gly-spin");
                        s.classList.add("glyphicon-tasks");
                        s.classList.remove("glyphicon-cog");
                    }
                }',
            ]) . ' <span>' . \Yii::t('ticket', 'Restores') . '</span></div>',
            Url::to(['restore/index', 'ticket_id' => $model->id, '#' => 'restores']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>
    <li title="<?= \Yii::t('ticket', 'History') ?>">
        <?= Html::a(
            '<div><i class="glyphicon glyphicon-book"></i> <span>' . \Yii::t('ticket', 'History') . '</span></div>',
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
                    ['backup', 'id' => $model->id, '#' => 'tab_backups'],
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