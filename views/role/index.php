<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;


/* @var $this yii\web\View */
/* @var $searchModel app\models\RoleSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \Yii::t('user', 'Roles');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="role-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php Pjax::begin(); ?>
    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [
            ['class' => 'kartik\grid\SerialColumn', 'order' => DynaGrid::ORDER_FIX_LEFT],

            [
                'attribute' => 'created_at',
                'format' => 'timeago',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'options' => ['placeholder' => \Yii::t('form', 'Enter day...')],
                    'pluginOptions' => [
                       'format' => 'yyyy-mm-dd',
                       'todayHighlight' => true,
                       'autoclose' => true,
                    ]
                ],
                'contentOptions' => [
                    'class' => 'col-md-2',
                ],
            ],
            [
                'attribute' => 'name',
                'filterType' => GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['role/index', 'mode' => 'list', 'attr' => 'role']),
                            'dataType' => 'json',
                            'delay' => 250,
                            'cache' => true,
                            'data' => new JsExpression('function(params) {
                                return {
                                    q: params.term,
                                    page: params.page,
                                    per_page: 10
                                };
                            }'),
                            'processResults' => new JsExpression('function(data, page) {
                                return {
                                    results: data.results,
                                    pagination: {
                                        more: data.results.length === 10 // If there are 10 matches, theres at least another page
                                    }
                                };
                            }'),
                        ],
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('function(q) { return q.text; }'),
                        'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                    ],
                ],
                'filterInputOptions' => [
                    'placeholder' => \Yii::t('form', 'Any')
                ],
                'format'=>'raw'
            ],
            'description',
            [
                'attribute' => 'updated_at',
                'format' => 'timeago',
                'filterType' => GridView::FILTER_DATE,
                'filterWidgetOptions' => [
                    'options' => ['placeholder' => \Yii::t('form', 'Enter day...')],
                    'pluginOptions' => [
                       'format' => 'yyyy-mm-dd',
                       'todayHighlight' => true,
                       'autoclose' => true,
                    ]
                ],
                'contentOptions' => [
                    'class' => 'col-md-2',
                ],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'order' => DynaGrid::ORDER_FIX_RIGHT,
                'contentOptions' => [
                    'class' => 'text-nowrap',
                    'style' => 'width:10px;',
                ],
            ],
        ],
        'storage' => DynaGrid::TYPE_COOKIE,
        'theme' => 'simple-default',
        'gridOptions' => [
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'panel' => ['heading' => '<h3 class="panel-title">' . \Yii::t('user', 'Roles') . '</h3>'],
            'toolbar' =>  [
                ['content' =>
                    Html::a('<i class="glyphicon glyphicon-plus"></i>&nbsp;' . \Yii::t('user', 'Create Role'), ['create'], ['data-pjax' => 0, 'class' => 'btn btn-success', 'title' => \Yii::t('user', 'Create Role')]) . ' ' .
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['/role/index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => \Yii::t('user', 'Reset Grid')])
                ],
                ['content' => '{dynagridFilter}{dynagridSort}{dynagrid}'],
                '{export}',
        ]            
        ],
        'options' => ['id' => 'dynagrid-role-index'] // a unique identifier is important
    ]); ?>

    <?= $this->render('@app/views/_notification') ?>

    <?php Pjax::end(); ?>


</div>
