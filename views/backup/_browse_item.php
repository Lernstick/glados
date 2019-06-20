<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $model app\models\Backup the data model */
/* @var $widget yii\widgets\ListView this widget instance */
/* @var $ticket app\models\Ticket the ticket data model */
/* @var $options array RdiffbackupFilesystem options array */

if ($model->type == 'dir') {

    if ($model->state != 'missing') {
        echo Html::a(
            '<span class="glyphicon glyphicon-folder-open"></span> ' . yii::$app->formatter->format($model->displayName, 'text'),
            Url::to([
                false,
                'id' => $ticket->id,
                'path' => $model->path,
                'date' => $date,
                'showDotFiles' => $options['showDotFiles'],
                '#' => 'browse',
            ]),
            []
        );
    } else {
        echo '<span><span class="glyphicon glyphicon-folder-open"></span> <del>' . yii::$app->formatter->format($model->displayName, 'text') . '</del></span>';
    }

} else {
    if ($model->state != 'missing') {
        if ($model->version !== null) {
            echo Html::a(
                '<span><span class="glyphicon glyphicon-file"></span> ' . yii::$app->formatter->format($model->displayName, 'text') . '</span>',
                Url::to([
                    'backup/file',
                    'ticket_id' => $ticket->id,
                    'path' => $model->path,
                    'date' => $model->version,
                    'showDotFiles' => $options['showDotFiles'],
                    '#' => 'browse',
                ]),
                [
                    'data-pjax' => 0,
                    'tabindex' => 0,
                ]
            );
    } else {
        echo '<span><span class="glyphicon glyphicon-file"></span> ' . yii::$app->formatter->format($model->displayName, 'text') . '</span>';
    }

    } else {
        if ($model->wasWhiteout == true && $model->isOldestVersion) {
            echo '<span><span class="glyphicon glyphicon-file"></span> ' . yii::$app->formatter->format($model->displayName, 'text') . '</span>';
        } else {
            echo '<span><span class="glyphicon glyphicon-file"></span> <del>' . yii::$app->formatter->format($model->displayName, 'text') . '</del></span>';
        }
    }
    echo "&nbsp;&nbsp;&nbsp;";
    echo "<span class='backup-browse-options'>";
    echo Html::a(
        '<span class="glyphicon glyphicon-list-alt"></span> ' . \Yii::t('ticket', 'View all versions'),
        Url::to([
            false,
            'id' => $ticket->id,
            'path' => $model->path,
            'showDotFiles' => $options['showDotFiles'],
            '#' => 'browse',
        ])
    );
    echo " ";
    echo Html::a(
        '<span class="glyphicon glyphicon-tasks"></span> ' . \Yii::t('ticket', 'Restore this state of the file'),
        Url::to([
            'ticket/restore',
            'id' => $ticket->id,
            'file' => $model->path,
            'date' => $model->version,
            'showDotFiles' => $options['showDotFiles'],
        ]),
        [
            'data-href' => Url::to([
                'ticket/restore',
                'id' => $ticket->id,
                'file' => $model->path,
                'date' => $model->version,
                'showDotFiles' => $options['showDotFiles'],
                '#' => 'tab_restores',
            ]),
            'data-toggle' => 'modal',
            'data-target' => '#confirmRestore',
            'data-path' => $model->path,
            'data-version' => yii::$app->formatter->format($model->version, 'backupVersion'),
        ]
    );    
    echo '</span>';
}

echo '<div class="pull-right">';
if ($model->type == 'dir') {
    if ($model->state == 'missing') {
        echo '<span class="text-muted">' . yii::$app->formatter->format($model->version, 'backupVersion') . ', <abbr title="' . \Yii::t('ticket', 'This directory does not exist in that version. Restoring it in this state will REMOVE the directory from the target machine.') . '">' . $model->state . '</abbr></span>';
    }
} else {
    if ($model->state != 'missing') {
        echo '<span class="text-muted">' . ($model->version == $model->newestBackupVersion ? '(' . \Yii::t('ticket', 'current') . ') ' : null) . yii::$app->formatter->format($model->version, 'backupVersion') . ', ' . yii::$app->formatter->format($model->mode, 'text') . ', ' . yii::$app->formatter->format($model->size, 'shortSize') . '</span>';
    } else {
        echo '<span class="text-muted">' . yii::$app->formatter->format($model->version, 'backupVersion');
        if (!($model->wasWhiteout == true && $model->isOldestVersion)) {
            echo ', <abbr title="' . \Yii::t('ticket', 'This file does not exist in that version. Restoring it in this state will REMOVE the file from the target machine.') . '">' . $model->state . '</abbr>';
        }
        echo '</span>';
    }
}
echo '</div>'

?>
