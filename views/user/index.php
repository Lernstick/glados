<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [
            ['class' => 'kartik\grid\SerialColumn', 'order' => DynaGrid::ORDER_FIX_LEFT],

            'username',
            [
                'attribute' => 'role',
                'filter' => $searchModel->roleList,
            ],
            'last_visited',

            ['class' => 'yii\grid\ActionColumn'],
        ],
        'storage' => DynaGrid::TYPE_COOKIE,
        'theme' => 'simple-default',
        'gridOptions' => [
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'panel' => ['heading' => '<h3 class="panel-title">Users</h3>'],
            'toolbar' =>  [
                ['content' =>
                    Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'], ['data-pjax' => 0, 'class' => 'btn btn-success', 'title' => 'Create User']) . ' ' .
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['/user/index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => 'Reset Grid'])
                ],
                ['content' => '{dynagridFilter}{dynagridSort}{dynagrid}'],
                '{export}',
        ]            
        ],
        'options' => ['id' => 'dynagrid-user-index'] // a unique identifier is important
    ]); ?>

</div>
