<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\elasticsearch\ActiveDataProvider */

use yii\helpers\Html;
use yii\widgets\ListView;

$this->title = \Yii::t('search', 'Search results');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-search">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php
    try {
        echo ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView' => '_search_result',
            'emptyText' => \Yii::t('search', 'No search results.'),
            'pager' => [
                'options' => [
                    'class' => 'pagination pagination-sm',
                ]
            ],
        ]);
    } catch (\Exception $e) {
        echo $e->getMessage();
    }
    ?>
   

</div>
