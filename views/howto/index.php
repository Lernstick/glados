<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\HowtoSearch */
/* @var $dataProvider yii\data\ArrayDataProvider */

$this->title = \Yii::t('help', 'Howtos');
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index']];
?>

<div class="howto-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'layout' => '{items} {summary} {pager}',
        'columns' => [
            [
                   'label' => \Yii::t('help', 'Guide / Howto'),
                   'format' => 'raw',
                    'value'=>function ($model) {
                        return Html::a($model->title, Url::to(['howto/view', 'id' => $model->id]));
                    },
            ],
        ],
    ]); ?>

</div>