<?php

use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket*/
/* @var $group string */

$event = substitute(empty($group) ? 'ticket/{id}' : '{group}:ticket/{id}', [
    'group' => $group,
    'id' => $model->id,
]);

/* spinning glyphicon */
echo ActiveEventField::widget([
    'options' => [
        'tag' => 'i',
        'class' => 'glyphicon glyphicon-cog ' . ($model->backup_lock == 1 ? 'gly-spin' : 'hidden'),
        'style' => 'float: left;',
    ],
    'event' => $event,
    'jsonSelector' => 'backup_lock',
    'jsHandler' => 'function(d, s){
        if(d == "1"){
            s.classList.add("gly-spin");
            s.classList.remove("hidden");
        }else if(d == "0"){
            s.classList.remove("gly-spin");
            s.classList.add("hidden");
        }
    }',
]);

/* backup state */
echo ActiveEventField::widget([
    'options' => [
        'style' => 'float:left'
    ],
    'content' => yii::$app->formatter->format($model->backup_state, 'ntext'),
    'event' => $event,
    'jsonSelector' => 'backup_state',
]);

/* last backup successful */
echo ActiveEventField::widget([
    'content' => substitute('&nbsp;<i class="glyphicon glyphicon-ok text-success"></i>&nbsp;{text}', [
      'text' => \Yii::t('ticket', 'last backup successful'),
    ]),
    'options' => [
        'class' => $model->last_backup == 1 ? '' : 'hidden'
    ],
    'event' => $event,
    'jsonSelector' => 'last_backup',
    'jsHandler' => 'function(d, s){
        if(d == "1"){
            s.classList.remove("hidden");
        }else if(d == "0"){
            s.classList.add("hidden");
        }
    }',
]);

/* last backup failed */
echo substitute('<div style="float:left;" class="{class}">&nbsp;<i class="glyphicon glyphicon-remove text-danger"></i>&nbsp;{text}</div>', [
    'class' => ($model->lastBackupFailed ? '' : 'hidden'),
    'text' => \Yii::t('ticket', 'last backup failed')
]);

/* abandoned text */
echo $model->abandoned ? substitute('&nbsp;<a tabindex="0" class="label label-danger" role="button" data-toggle="popover" data-html="true" data-trigger="focus" title="{title}" data-content="{text}">{content}</a>', [
    'title' => \Yii::t('ticket', 'Abandoned Ticket'),
    'text' => \Yii::t('ticket', 'This ticket is abandoned and thus excluded from regular backup. A reason for this could be that the backup process was not able to perform a backup of the client. After some time of failed backup attempts, the ticket will be abandoned (the value of <i>Time Limit</i> of this ticket/exam or <i>{default}</i> if nothing is set). You can still force a backup by clicking Actions->Backup Now.', ['default' => yii::$app->formatter->format(\Yii::$app->params['abandonTicket'], 'duration')]),
    'content' => \Yii::t('ticket', 'Abandoned'),
]) : '';
