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

    <div class="dropdown">
      <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <i class="glyphicon glyphicon-list-alt"></i>
        Actions&nbsp;<span class="caret"></span>
      </button>
      <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
        <li>
            <?= Html::a('<span class="glyphicon glyphicon-plus"></span> Create Exam', ['create']) ?>
        </li>
      </ul>
    </div>
    <br>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'layout' => '{items} {summary} {pager}',
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
