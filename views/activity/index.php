<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveField;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use kartik\dynagrid\DynaGrid;
use kartik\select2\Select2;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ActivitySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $last_visited */

$this->title = \Yii::t('activities', 'Activities');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="activity-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= DynaGrid::widget([
        'showPersonalize' => true,
        'columns' => [
            [
                'attribute' => 'date',
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
                'attribute' => 'token',
                'value' => function ($model) {
                    return Html::a(
                        $model->ticket->token,
                        ['ticket/view', 'id' => $model->ticket->id],
                        ['data-pjax' => 0]
                    );
                },
                'filterType'=>GridView::FILTER_SELECT2,
                'filterWidgetOptions'=>[
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'minimumInputLength' => 3,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['ticket/index', 'mode' => 'list','attr' => 'token']),
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
                'format'=>'raw',
                'contentOptions' => [
                    'class' => 'col-md-1',
                ],
            ],
            [
                'attribute' => 'description',
                'value' => function ($model) {
                    return \Yii::t(null, $model->tr_activity_description->de, $model->params, 'xxx');
                },
                'format' => 'raw',
                'filterType' => GridView::FILTER_SELECT2,
                'filterWidgetOptions' => [
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'placeholder' => '',
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['activity/index', 'mode' => 'list', 'attr' => 'description']),
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
                'contentOptions' => [
                    'class' => 'col-md-8',
                ],
            ],
        ],
        'storage' => DynaGrid::TYPE_COOKIE,
        'theme' => 'simple-default',
        'gridOptions' => [
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'panel' => ['heading' => '<h3 class="panel-title">' . \Yii::t('activities', 'Activities') . '</h3>'],
            'toolbar' =>  [
                ['content' => $form->field($searchModel, 'severity', [
                        'options' => [
                            'class' => 'select2-activity-severity',
                        ]
                    ])->label(false)->widget(Select2::classname(), [
                        'data' => $searchModel->nameMap,
                        'hideSearch' => true,
                        'options' => [
                            'multiple' => true,
                            'placeholder' => \Yii::t('activities', 'Severity'),
                        ],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'width' => '100%',
                        ],
                        'pluginEvents' => [
                            'change' => 'function() { this.form.submit() }',
                        ]
                    ])
                ],
                ['content' =>
                    Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['/activity/index'], ['data-pjax' => 0, 'class' => 'btn btn-default', 'title' => \Yii::t('activities', 'Reset Grid')])
                ],
                ['content' => '{dynagridFilter}{dynagridSort}{dynagrid}'],
                '{export}',
            ],
            'rowOptions' => function($model) {
                return array_key_exists($model->severity, $model->classMap) ? ['class' => $model->classMap[$model->severity]] : null;
            },    
        ],
        'options' => ['id' => 'dynagrid-activities-index'] // a unique identifier is important
    ]); ?>

    <?php ActiveForm::end(); ?>

    <?php Pjax::end(); ?>

</div>
