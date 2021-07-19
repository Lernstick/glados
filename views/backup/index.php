<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ListView;
use app\components\ActiveEventField;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;
use app\components\Editable;

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
            'attribute' => 'last_backup',
            'format' => 'raw',
            'value' =>  ActiveEventField::widget([
                'content' => $ticketModel->last_backup,
                'event' => 'ticket/' . $ticketModel->id,
                'jsonSelector' => 'last_backup',
            ]),
            'visible' => YII_ENV_DEV,
            'captionOptions' => ['class' => 'dev_item']
        ],
        [
            'attribute' => 'backup_interval',
            'value' => Editable::widget([
                'content' => ($ticketModel->backup_interval == 0 ? \Yii::t('ticket', 'No Backup') : yii::$app->formatter->format($ticketModel->backup_interval, 'duration')),
                'editUrl' => ['ticket/update', 'id' => $ticketModel->id, 'mode' => 'editable', 'attr' => 'backup_interval' ],
            ]),
            'format' => 'raw'
        ],
        [
            'attribute' => 'backup_last',
            'format' => 'raw',
            'value' => yii::$app->formatter->format($ticketModel->backup_last, 'timeago') . ' (<b>' . \Yii::t('ticket', 'last try') . '</b>: ' . yii::$app->formatter->format($ticketModel->backup_last_try, 'timeago') . ')',
        ],        
        'backup_size:shortSize',
        [
            'attribute' => 'backup_state',
            'format' => 'links',
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
    'emptyText' => \Yii::t('ticket', 'No backups found.'),
    'layout' => '{items} <br>{summary} {pager}',
    'pager' => [
        'class' => app\widgets\CustomPager::className(),
        'selectedLayout' => Yii::t('app', '{selected} <span style="color: #737373;">items</span>'),
    ],
]); ?>

<?php

Modal::begin([
    'id' => 'backupLogModal',
    'header' => '<h4>' . \Yii::t('ticket', 'Backup Log') . '</h4>',
    'footer' => Html::Button(\Yii::t('ticket', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]);

    Pjax::begin([
        'id' => 'backupLogModalContent',
    ]);
    Pjax::end();

Modal::end();

?>