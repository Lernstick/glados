<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LogSearch */
/* @var $dataProvider yii\data\ArrayDataProvider */

$this->title = \Yii::t('server', 'Server Logs');
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['logs']];

?>
<div class="logs-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="tab-content">

        <?= $this->render('/log/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]) ?>

    </div>

</div>