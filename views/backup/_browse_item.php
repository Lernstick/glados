<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $model app\models\Backup the data model */
/* @var $widget yii\widgets\ListView this widget instance */
/* @var $ticket app\models\Ticket the ticket data model */

if ($model->type == 'dir') {

    if ($model->state != 'missing') {
        echo Html::a(
            '<span class="glyphicon glyphicon-folder-open"></span> ' . $model->displayName,
            Url::to([
                false,
                'id' => $ticket->id,
                'path' => $model->path,
                'date' => $date,
                '#' => 'browse',
            ]),
            []
        );
    } else {
        echo '<span><span class="glyphicon glyphicon-folder-open"></span> <del>' . $model->displayName . '</del> (' . yii::$app->formatter->format($model->version, 'backupVersion') . ', <abbr title="This directory does not exist in that version. Restoring it in this state will REMOVE the directory from the target machine.">' . $model->state . '</abbr>)</span>';
    }

} else {
    if ($model->state != 'missing') {
        echo Html::a(
            '<span><span class="glyphicon glyphicon-file"></span> ' . $model->displayName . ' (' . yii::$app->formatter->format($model->version, 'backupVersion') . ($model->version == $model->newestBackupVersion ? ' (current)' : null) . ', ' . $model->state . ', ' . $model->mode . ', ' . yii::$app->formatter->format($model->size, 'shortSize') . ')</span>',
            Url::to([
                'backup/file',
                'ticket_id' => $ticket->id,
                'path' => $model->path,
                'date' => $model->version,
                '#' => 'browse',
            ]),
            [
                'data-pjax' => 0,
                'tabindex' => 0,
            ]
        );

    } else {
        echo '<span><span class="glyphicon glyphicon-file"></span> <del>' . $model->displayName . '</del> (' . yii::$app->formatter->format($model->version, 'backupVersion') . ', <abbr title="This file does not exist in that version. Restoring it in this state will REMOVE the file from the target machine.">' . $model->state . '</abbr>)</span>';
    }
    echo "&nbsp;&nbsp;&nbsp;";
    echo "<span class='backup-browse-options'>";
    echo Html::a(
        '<span class="glyphicon glyphicon-list-alt"></span> View all versions',
        Url::to([
            false,
            'id' => $ticket->id,
            'path' => $model->path,
            '#' => 'browse',
        ])
    );
    echo " ";
    echo Html::a(
        '<span class="glyphicon glyphicon-tasks"></span> Restore this state of the file',
        Url::to([
            'ticket/restore',
            'id' => $ticket->id,
            'file' => $model->path,
            'date' => $model->version
        ]),
        [
            'data-href' => Url::to([
                'ticket/restore',
                'id' => $ticket->id,
                'file' => $model->path,
                'date' => $model->version,
                '#' => 'tab_restores',
            ]),
            'data-toggle' => 'modal',
            'data-target' => '#confirmRestore',
            'data-path' => $model->path,
            'data-version' => yii::$app->formatter->format($model->version, 'backupVersion'),
        ]
    );    
    echo "</span>";
}

?>
