<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Config */

$this->title = 'System Configuation';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['system']];
?>
<div class="config-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'avahiServiceFile',
            'port',
            'type',
            [
                'attribute' => 'txtRecords',
                'format' => 'raw',
                'value' =>  implode('<br>', $model->txtRecords)
            ],
        ],
    ]) ?>

</div>