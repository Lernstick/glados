<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

$this->title = \Yii::t('results', 'Status');
$this->params['breadcrumbs'][] = ['label' => $model->token];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="result-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_view', [
        'model' => $model,
    ]) ?>

</div>
