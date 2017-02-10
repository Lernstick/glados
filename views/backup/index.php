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
        ],
        'backup_last:timeago',
        'backup_last_try:timeago',
        [
            'attribute' => 'backup_state',
            'format' => 'raw',
            'value' =>  ActiveEventField::widget([
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