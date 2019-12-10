<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use \yii\helpers\Url;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;


/* @var $this yii\web\View */
/* @var $searchModel app\models\AuthenticationSearch */
/* @var $dataProvider yii\data\ArrayDataProvider */

$this->title = \Yii::t('auth', 'Authentication Methods');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="auth-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>
    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [
            'order',
            'name',
            'typeName',
            'description',
            [
                'attribute' => 'loginScheme',
                'visible' => false
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'order' => DynaGrid::ORDER_FIX_RIGHT,
                'contentOptions' => [
                    'class' => 'text-nowrap',
                    'style' => 'width:10px;',
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    return Url::to([$action, 'id' => $model->id]);
                },
                'visibleButtons' => [
                    'update' => function ($model) {
                        return $model->id != "0";
                    },
                    'delete' => function ($model) {
                        return $model->id != "0";
                    },
                ],
            ],
        ],
        'storage' => DynaGrid::TYPE_COOKIE,
        'theme' => 'simple-default',
        'gridOptions' => [
            'dataProvider' => $dataProvider,
            'panel' => ['heading' => '<h3 class="panel-title">' . \Yii::t('auth', 'Authentication Methods') . '</h3>'],
            'rowOptions' => function($model) {
                return $model->id == "0" ? ['class' => 'info'] : null;
            },   
            'toolbar' =>  [
                ['content' =>
                    Html::a('<i class="glyphicon glyphicon-plus"></i>&nbsp;' . \Yii::t('auth', 'Add new Authentication Method'), ['create'], ['data-pjax' => 0, 'class' => 'btn btn-success', 'title' => \Yii::t('auth', 'Add new Authentication Method')]) . 
                    '<div class="btn-group" role="group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="glyphicon glyphicon-list-alt"></i>&nbsp;' . \Yii::t('auth', 'Actions') . '<span class="caret"></span></button>
                        <ul class="dropdown-menu">
                          <li>' . Html::a('<i class="glyphicon glyphicon-user"></i>&nbsp;' . \Yii::t('auth', 'Test Login'), ['test'], ['data-pjax' => 0, 'title' => \Yii::t('auth', 'Test Login')]) . '</li>
                          <li>' . Html::a('<i class="glyphicon glyphicon-retweet"></i>&nbsp;' . \Yii::t('auth', 'Migrate Users'), ['migrate'], ['data-pjax' => 0, 'title' => \Yii::t('auth', 'Migrate Users')]) . '</li>
                        </ul>
                    </div>' . 
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['/auth/index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => \Yii::t('auth', 'Reset Grid')])
                ],
                ['content' => '{dynagrid}'],
                '{export}',
            ]
        ],
        'options' => ['id' => 'dynagrid-auth-index'], // a unique identifier is important
    ]); ?>

    <?= $this->render('@app/views/_notification') ?>

    <?php Pjax::end(); ?>


</div>
