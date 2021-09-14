<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ListView;
use app\components\ActiveEventField;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\RestoreSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

?>

<div class="alert alert-info" role="alert">
    <span class="glyphicon glyphicon-alert"></span>
    <span><?= \Yii::t('ticket', 'Do you want to restore a file? Then please visit {link}.', [
        'link' => Html::a('Manual / Restore a specific file', ['/howto/view', 'id' => 'restore-specific-file.md'], ['class' => 'alert-link'])
    ]) ?></span>
</div>

<?= DetailView::widget([
    'model' => $ticketModel,
    'attributes' => [
        [
            'attribute' => 'restore_lock',
            'format' => 'raw',
            'value' =>  ActiveEventField::widget([
                'content' => $ticketModel->restore_lock,
                'event' => 'ticket/' . $ticketModel->id,
                'jsonSelector' => 'restore_lock',
            ]),
            'visible' => YII_ENV_DEV,
            'captionOptions' => ['class' => 'dev_item']
        ],
        [
            'attribute' => 'restore_state',
            'format' => 'links',
            'value' =>  ActiveEventField::widget([
                    'options' => [
                        'tag' => 'i',
                        'class' => 'glyphicon glyphicon-cog ' . ($ticketModel->restore_lock == 1 ? 'gly-spin' : 'hidden'),
                        'style' => 'float: left;',
                    ],
                    'event' => 'ticket/' . $ticketModel->id,
                    'jsonSelector' => 'restore_lock',
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
                'content' => yii::$app->formatter->format($ticketModel->restore_state, 'ntext'),
                'event' => 'ticket/' . $ticketModel->id,
                'jsonSelector' => 'restore_state',
            ]),
        ],
    ],
]) ?>

<?= ListView::widget([
    'dataProvider' => $dataProvider,
    'options' => ['id' => 'restores-accordion', 'class' => 'panel-group'],
    'itemOptions' => ['class' => 'panel panel-default'],
    'itemView' => '_item',
    'emptyText' => \Yii::t('ticket', 'No restores found.'),
    'layout' => '{items} <br>{summary} {pager}',
    'pager' => [
        'class' => app\widgets\CustomPager::className(),
        'selectedLayout' => Yii::t('app', '{selected} <span style="color: #737373;">items</span>'),
    ],
]); ?>

<?php

Modal::begin([
    'id' => 'restoreLogModal',
    'header' => '<h4>' . \Yii::t('ticket', 'Restore Log') . '</h4>',
    'footer' => Html::Button(\Yii::t('ticket', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]);

    Pjax::begin([
        'id' => 'restoreLogModalContent',
    ]);
    Pjax::end();

Modal::end();

?>