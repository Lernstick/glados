<?php

/* @var $this yii\web\View */
/* @var $searchModel app\models\forms\Search */
/* @var $dataProvider yii\elasticsearch\ActiveDataProvider */

use yii\helpers\Html;
use yii\widgets\ListView;
use yii\widgets\ActiveForm;

$this->title = \Yii::t('search', 'Search results');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-search">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'id' => 'search-form',
        'action' => [''],
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($searchModel, 'q')->textInput(); ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($searchModel, 'index')->dropdownList([
                    '' => \Yii::t('search', 'All'),
                    'user' => \Yii::t('seach', 'Only users'),
                    'exam' => \Yii::t('seach', 'Only exams'),
                    'ticket' => \Yii::t('seach', 'Only tickets'),
                    'backup' => \Yii::t('seach', 'Only backups'),
                    'restore' => \Yii::t('seach', 'Only restores'),
                    'howto' => \Yii::t('seach', 'Only howtos'),
                    'log' => \Yii::t('seach', 'Only logs'),
                    'file' => \Yii::t('seach', 'Only files'),
                ]); ?>
        </div>
    </div>

    <hr>

    <?php ActiveForm::end(); ?>

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
