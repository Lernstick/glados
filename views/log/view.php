<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Log */

$this->params['breadcrumbs'][] = ['label' => \Yii::t('log', 'Log')];
$this->params['breadcrumbs'][] = $model->path;

?>

<div class="log-view">
    <div class="panel panel-default">
        <div class="panel-heading">
            <code><?= Html::encode($model->path) ?></code>
        </div>
        <div style='overflow-x: auto'>
            <?= $this->render('_view', [
                'model' => $model,
            ]) ?>
        </div>
    </div>
</div>
