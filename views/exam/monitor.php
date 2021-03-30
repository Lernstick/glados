<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use yii\bootstrap\Modal;

/* TODO: remove this file */

/* @var $this yii\web\View */
/* @var $model app\models\Exam */
/* @var $searchModel app\models\TicketSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \Yii::t('exams', 'Exams'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="exam-view container">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_nav', [
        'model' => $model,
        'tab' => 'monitor',
    ]) ?>

    <p></p>

    <div class="tab-content">

        <div id="monitor" class="tab-pane fade">

            <?php $_GET = array_merge($_GET, ['#' => 'tab_monitor']); ?>

            <?= $this->render('/monitor/_monitor', [
                'exam' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]) ?>

        </div>

    </div>

</div>
