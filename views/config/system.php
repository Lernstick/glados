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
            'avahiPort',
            'avahiType',
            [
                'attribute' => 'avahiTxtRecords',
                'format' => 'raw',
                'value' =>  implode('<br>', $model->avahiTxtRecords)
            ],
            'host',
            'ip',
            'port',
        ],
    ]) ?>

</div>