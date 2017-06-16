<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ListView;
use app\components\ActiveEventField;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\BackupSearch */
/* @var $dataProvider yii\data\ArrayDataProvider */

?>

<?= DetailView::widget([
    'model' => $ticketModel,
    'attributes' => [
        [
            'attribute' => 'backup_lock',
            'format' => 'raw',
            'value' =>  ActiveEventField::widget([
                'content' => $ticketModel->backup_lock,
                'event' => 'ticket/' . $ticketModel->id,
                'jsonSelector' => 'backup_lock',
            ]),
            'visible' => YII_ENV_DEV,
            'captionOptions' => ['class' => 'dev_item']
        ],
        [
            'attribute' => 'backup_interval',
            'format' => 'raw',
            'value' =>  $ticketModel->backup_interval == 0 ? 'No Backup' : yii::$app->formatter->format($ticketModel->backup_interval, 'duration'),
        ],
        [
            'attribute' => 'backup_last',
            'format' => 'raw',
            'value' => yii::$app->formatter->format($ticketModel->backup_last, 'timeago') . ' (<b>last try</b>: ' . yii::$app->formatter->format($ticketModel->backup_last_try, 'timeago') . ')',
        ],        
        //'backup_last:timeago',
        //'backup_last_try:timeago',
        'backup_size:shortSize',        
        [
            'attribute' => 'backup_state',
            'format' => 'raw',
            'value' =>  ActiveEventField::widget([
                    'options' => [
                        'tag' => 'i',
                        'class' => 'glyphicon glyphicon-cog ' . ($ticketModel->backup_lock == 1 ? 'gly-spin' : 'hidden'),
                        'style' => 'float: left;',
                    ],
                    'event' => 'ticket/' . $ticketModel->id,
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
                ]) . ActiveEventField::widget([
                'content' => yii::$app->formatter->format($ticketModel->backup_state, 'ntext'),
                'event' => 'ticket/' . $ticketModel->id,
                'jsonSelector' => 'backup_state',
            ]),
        ],
    ],
]) ?>

<?= ListView::widget([
    'dataProvider' => $dataProvider,
    'options' => ['id' => 'backups-accordion', 'class' => 'panel-group'],
    'itemOptions' => ['class' => 'panel panel-default'],
    'itemView' => '_item',
    'emptyText' => 'No backups found.',
    'layout' => '{items} <br>{summary} {pager}',
]); ?>

<?php

Modal::begin([
    'id' => 'backupLogModal',
    'header' => '<h4>Backup Log</h4>',
    'footer' => Html::Button('Close', ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]);

    Pjax::begin([
        'id' => 'backupLogModalContent',
    ]);
    Pjax::end();

Modal::end();

?>