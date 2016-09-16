<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ExamSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Exams';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="exam-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Exam', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'name',
            'subject',
            [
                'attribute' => 'user.username',
                'label' => 'Owner',
                'value' => function($model){
                    return ( $model->user_id == null ? '<span class="not-set">(user removed)</span>' : '<span>' . $model->user->username . '</span>' );
                },
                'format' => 'html',
            ],            
            'ticketCount',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete} {create-many}',
                'buttons' => [
                    'create-many' => function ($url) {
                        return Html::a('<span class="glyphicon glyphicon-plus-sign"></span>', $url,
                            [
                                'title' => 'Create Tickets',
                                'data-pjax' => '0',
                            ]
                        );
                    },
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    if ($action === 'create-many') {
                        return Url::toRoute(['ticket/create-many', 'exam_id' => $model->id]);
                    }
                    return Url::toRoute(['exam/' . $action, 'id' => $model->id]);
                },
            ],
        ],
    ]); ?>

    <?= $this->render('@app/views/_notification', [
        'session' => $session,
    ]) ?>

    <?php Pjax::end(); ?>

</div>
