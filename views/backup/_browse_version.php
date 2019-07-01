<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $model string the data model */
/* @var $widget yii\widgets\ListView this widget instance */
/* @var $ticket app\models\Ticket the ticket data model */
/* @var $fs app\models\RdiffFileSystem the Rdiff data model */
/* @var $date string the date */
/* @var $options array RdiffbackupFilesystem options array */

echo Html::a(
    ($model == 'all' ? \Yii::t('ticket', 'All versions overlapping') : yii::$app->formatter->format($model, 'datetime') . ($model == $fs->newestBackupVersion ? ' (' . \Yii::t('ticket', 'current') . ')' : null)) . ' <span class="glyphicon glyphicon-ok" style="visibility:' . ($date == $model ? 'visible' : 'hidden') . ';"></span>',
    Url::to([
        false,
        'id' => $ticket->id,
        'path' => $fs->path,
        'date' => $model,
        'showDotFiles' => $options['showDotFiles'],
        '#' => 'browse'
    ]),
    ['data-pjax' => 0]
);
?>