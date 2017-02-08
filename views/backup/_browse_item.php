<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $model app\models\Backup the data model */
/* @var $widget yii\widgets\ListView this widget instance */
/* @var $ticket app\models\Ticket the ticket data model */

if ($model->type == 'dir') {
    echo Html::a(
        '<span class="glyphicon glyphicon-folder-open"></span> ' . $model->displayName,
        Url::to([
            false,
            'id' => $ticket->id,
            'path' => $model->path,
            'date' => $model->version,
            '#' => 'browse',
        ]),
        []
    );
    echo "&nbsp;&nbsp;&nbsp;";
    echo "<span class='backup-browse-options'>(";
    echo Html::a(
        '<span class="glyphicon glyphicon-tasks"></span> Restore this directory',
        Url::to([
            'ticket/restore',
            'id' => $ticket->id,
            'file' => $model->path,
            'date' => $model->version
        ]),
        [
            'id' => 'restore-now'
        ]
    );    
    echo ")</span>";

} else {
    if ($model->state != 'missing') {
        echo Html::a(
            '<span class="glyphicon glyphicon-file"></span> ' . $model->displayName . ' (' . yii::$app->formatter->format($model->version, 'backupVersion') . ', ' . $model->state . ')',
            Url::to([
                'backup/file',
                'ticket_id' => $ticket->id,
                'path' => $model->path,
                'date' => $model->version,
                '#' => 'browse',
            ]),
            ['data-pjax' => 0]
        );
    } else {
        echo '<span class="glyphicon glyphicon-file"></span> <del>' . $model->displayName . '</del> (' . yii::$app->formatter->format($model->version, 'backupVersion') . ', <abbr title="This file does not exist in that version. Restoring it in this state will REMOVE the file from the target machine.">' . $model->state . '</abbr>)';
    }
    echo "&nbsp;&nbsp;&nbsp;";
    echo "<span class='backup-browse-options'>(";
    echo Html::a(
        '<span class="glyphicon glyphicon-list-alt"></span> View all versions',
        Url::to([
            false,
            'id' => $ticket->id,
            'path' => $model->path,
            '#' => 'browse',
        ])
    );
    echo ", ";
    echo Html::a(
        '<span class="glyphicon glyphicon-tasks"></span> Restore this state of the file',
        Url::to([
            'ticket/restore',
            'id' => $ticket->id,
            'file' => $model->path,
            'date' => $model->version
        ]),
        [
            'id' => 'restore-now',
        ]
    );    
    echo ")</span>";
}

?>