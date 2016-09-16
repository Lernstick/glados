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
            '<i class="glyphicon glyphicon-home"></i> General',
            Url::to(['exam/view', 'id' => $model->id, '#' => 'general']),
            ['data-toggle' => '']
        ); ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-th"></i> Monitor',
            Url::to(['exam/view', 'id' => $model->id, 'mode' => 'monitor', '#' => 'monitor']),
            ['data-toggle' => '']
        ); ?>
    </li>
    <li>
        <?= Html::a(
            '<i class="glyphicon glyphicon-stats"></i> Chart',
            Url::to(['exam/view', 'id' => $model->id, '#' => 'chart']),
            ['data-toggle' => '']
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
                    '<span class="glyphicon glyphicon-trash"></span> Delete',
                    ['delete', 'id' => $model->id],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'disabled' => $model->ticketCount != 0,
                        'data' => [
                            'confirm' => 'Are you sure you want to delete this item?',
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-save-file"></span> Generate PDFs',
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
                    '<span class="glyphicon glyphicon-trash"></span> Delete all Open Tickets',
                    ['ticket/delete', 'mode' => 'many', 'exam_id' => $model->id],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'disabled' => $model->openTicketCount == 0,
                        'data' => [
                            'confirm' => 'Are you sure you want to delete ALL ' . $model->openTicketCount . ' Open tickets associated to this exam?',
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            </li>
            <li>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-globe"></span> Create 10 anonymous Tickets',
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
                    '<span class="glyphicon glyphicon-user"></span> Create assigned Tickets',
                    ['ticket/create', 'mode' => 'many', 'exam_id' => $model->id, 'type' => 'assigned'],
                    [
                        'class' => 'btn',
                        'style' => ['text-align' => 'left'],
                        'disabled' => !$model->fileConsistency,
                    ]
                ) ?>
            </li>                
        </ul>
    </li>

</ul>