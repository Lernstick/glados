<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;


/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php Pjax::begin(); ?>
    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [
            ['class' => 'kartik\grid\SerialColumn', 'order' => DynaGrid::ORDER_FIX_LEFT],

            //'username',
            [
                'attribute'=>'username',
                'filterType'=>GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'name' => 'UserSearch[username]',
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'minimumInputLength' => 2,
                        'placeholder' => '',
                        'language' => [
                            'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                        ],
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['user/index', 'mode' => 'list', 'attr' => 'username']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) { return {q:params.term}; }')
                        ],
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('function(q) { return q.text; }'),
                        'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                    ],
                ],
                'filterInputOptions' => [
                    'placeholder' => 'Any'
                ],
                'format'=>'raw'
            ],
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

    <?php Pjax::end(); ?>


</div>
