<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $model string the data model */
/* @var $widget yii\widgets\ListView this widget instance */
/* @var $ticket app\models\Ticket the ticket data model */
/* @var $fs app\models\RdiffFileSystem the Rdiff data model */

echo Html::a(
    ($model == 'all' ? 'All versions overlapping' : ($model == 'now' ? 'Current version' : yii::$app->formatter->format($model, 'datetime'))) . ' <span class="glyphicon glyphicon-ok" style="visibility:' . ($date == $model ? 'visible' : 'hidden') . ';"></span>',
    Url::to([
        false,
        'id' => $ticket->id,
        'path' => $fs->path,
        'date' => $model,
        '#' => 'browse'
    ]),
    ['data-pjax' => 0]
);
?>