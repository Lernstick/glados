<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */
/* @var $searchModel app\models\TicketSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \Yii::t('monitor', 'Monitoring of the exam: {exam}', [
    'exam' => $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => \Yii::t('exams', 'Exams'), 'url' => ['exam/index']];
$this->params['breadcrumbs'][] = ['label' => \Yii::t('monitor', 'Monitoring'), 'url' => ['monitor/']];
$this->params['breadcrumbs'][] = $model->name;

?>

<div class="monitor-view container">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="tab-content">

        <?= $this->render('/monitor/_monitor', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]) ?>

    </div>

</div>
