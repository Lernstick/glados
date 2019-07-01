<?php

/* @var $this yii\web\View */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Markdown;

$this->title = \Yii::t('help', 'Howto') . ': ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => \Yii::t('help', 'Howtos'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$title = $model->title;
?>

<div class="howto-view">

<div class="row">

    <div class="markdown-view col-sm-8">

        <h1><?= Html::encode($model->title) ?></h1>

        <?= Markdown::process($model->content, 'gfm'); ?>

    </div>

    <div class="howto-nav-view col-sm-4">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'tableOptions' => [
                'class' => 'table table-bordered table-hover',
            ],
            'layout' => '{items} {pager}',
            'columns' => [
                [
                    'label' => \Yii::t('help', 'Navigation'),
                    'format' => 'raw',
                    'value'=> function ($model) use ($title) {
                        $a = $model->title == $title ? '<b>' . $model->title . '</b>' : $model->title;
                        return Html::a($a, Url::to(['howto/view', 'id' => $model->id]));
                    },
                ],
            ],
        ]); ?>
    </div>

</div>

</div>