<?php

use yii\widgets\ListView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\HistorySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model yii\base\Model the data model of the Item (for example: app\models\Ticket, app\models\Exam, app\models\User, ...) */

?>

<?php Pjax::begin() ?>

<?= ListView::widget([
    'dataProvider' => $dataProvider,
    'options' => [
        'tag' => 'ul',
        'class' => 'list-unstyled timeline widget',
    ],
    'itemView' => '_item',
    'itemOptions' => ['tag' => 'li'],
    'viewParams' => ['itemModel' => $model],
    'summaryOptions' => [
        'class' => 'summary col-xs-12 col-md-12',
    ],            
    'emptyText' => \Yii::t('ticket', 'No history items found.'),
    'layout' => '{items} <br>{summary} {pager}',
]); ?>

<?php Pjax::end() ?>
