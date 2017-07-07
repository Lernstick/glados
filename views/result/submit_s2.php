<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */

$this->title = 'Submit Results';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['result/submit']];
$this->params['breadcrumbs'][] = 'Step 2';

?>
<div class="result-create">

    <h1><?= Html::encode($this->title . ' - Step 2') ?></h1>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'layout' => '{items} {summary} {pager}',
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'test_taker',
            'token',            
            'examName',
        ],
    ]); ?>
    <?php Pjax::end(); ?>

    <?php $form = ActiveForm::begin([
        'action' => ['result/submit', 'mode' => 'step3', 'hash' => $model->hash],
    ]); ?>

    <div class="form-group">
        <?= Html::submitButton('Next Step', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
