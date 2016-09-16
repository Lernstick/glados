<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ActivitySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $last_visited */

$this->title = 'Activities';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="activity-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(['id' => 'activities']); ?>

    <?= $this->render('_item', [
        'model' => $searchModel,
        'dataProvider' => $dataProvider,
    ]); ?>

    <?php Pjax::end(); ?>

</div>
