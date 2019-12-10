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

// Javascript to enable link to tab
$(window).bind('hashchange', function() {
    var prefix = "tab_";
    $('.nav-tabs a[href*="' + document.location.hash.replace(prefix, "") + '"]').tab('show');
}).trigger('hashchange');
JS;
$this->registerJs($active_tabs);

?>

<ul class="nav nav-tabs">
    <li class="active">
        <?= Html::a(
            '<i class="glyphicon glyphicon-home"></i> ' . \Yii::t('exams', 'General'),
            Url::to(['exam/view', 'id' => $model->id, '#' => 'general']),
            ['data-toggle' => '']
        ); ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-file"></i> ' . \Yii::t('exams', 'Exam Files'),
            Url::to(['exam/view', 'id' => $model->id, '#' => 'file']),
            ['data-toggle' => '']
        ); ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-cog"></i> ' . \Yii::t('exams', 'Settings'),
            Url::to(['exam/view', 'id' => $model->id, '#' => 'settings']),
            ['data-toggle' => '']
        ); ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-th"></i> ' . \Yii::t('exams', 'Monitor'),
            Url::to(['exam/view', 'id' => $model->id, 'mode' => 'monitor', '#' => 'monitor']),
            ['data-toggle' => '']
        ); ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-book"></i> ' . \Yii::t('ticket', 'History'),
            Url::to(['exam/view', 'id' => $model->id, '#' => 'history']),
            ['data-toggle' => 'tab']
        ); ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-stats"></i> ' . \Yii::t('exams', 'Chart'),
            Url::to(['exam/view', 'id' => $model->id, '#' => 'chart']),
            ['data-toggle' => '']
        ); ?>
    </li>
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="glyphicon glyphicon-list-alt"></i>
            <?= \Yii::t('exams', 'Actions') ?><span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-pencil"></span> ' . \Yii::t('exams', 'Edit'),
                    ['update', 'id' => $model->id],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'disabled' => $model->runningTicketCount != 0,
                        'data-pjax' => 0
                    ]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-trash"></span> ' . \Yii::t('exams', 'Delete'),
                    ['delete', 'id' => $model->id],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'disabled' => $model->ticketCount != 0,
                        'data' => [
                            'confirm' => \Yii::t('exams', 'Are you sure you want to delete this exam?'),
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-save-file"></span> ' . \Yii::t('exams', 'Generate PDFs'),
                    ['view', 'mode' => 'report', 'id' => $model->id],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'data-pjax' => 0
                    ]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-trash"></span> ' . \Yii::t('exams', 'Delete all Open Tickets'),
                    ['ticket/delete', 'mode' => 'manyOpen', 'exam_id' => $model->id],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'disabled' => $model->openTicketCount == 0,
                        'data' => [
                            'confirm' => \Yii::t('exams', 'Are you sure you want to delete ALL {n} Open tickets associated to this exam?', ['n' => $model->openTicketCount]),
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-trash"></span> ' . \Yii::t('exams', 'Delete <b>ALL</b> associated Tickets'),
                    ['ticket/delete', 'mode' => 'many', 'exam_id' => $model->id],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'disabled' => $model->ticketCount == 0,
                        'data' => [
                            'confirm' => \Yii::t('exams', 'Are you sure you want to delete ALL {n} Tickets associated to this exam?', ['n' => $model->ticketCount]),
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-globe"></span> ' . \Yii::t('exams', 'Create 10 anonymous Tickets'),
                    ['ticket/create', 'mode' => 'many', 'exam_id' => $model->id, 'type' => 'anonymous', 'count' => 10],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'disabled' => !$model->fileConsistency,
                    ]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-user"></span> ' . \Yii::t('exams', 'Create assigned Tickets'),
                    ['ticket/create', 'mode' => 'many', 'exam_id' => $model->id, 'type' => 'assigned'],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'disabled' => !$model->fileConsistency,
                    ]
                ) ?>
            </li>
            <li class="divider"></li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-compressed"></span> ' . \Yii::t('exams', 'Generate ZIP-File with results'),
                    Url::to(['result/generate', 'exam_id' => $model->id]),
                    ['data-pjax' => 0]
                ); ?>
            </li>
           <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-question-sign"></span> ' . \Yii::t('exams', 'Visit the Manual'),
                    Url::to(['howto/view', 'id' => 'exam-view.md']),
                    ['data-pjax' => 0]
                ); ?>
            </li>
        </ul>
    </li>

</ul>