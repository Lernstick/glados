<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\ActiveField;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TicketSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Tickets';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ticket-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <div class="dropdown">
      <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <i class="glyphicon glyphicon-list-alt"></i>
        Actions&nbsp;<span class="caret"></span>
      </button>
      <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
        <li>
            <?= Html::a('<span class="glyphicon glyphicon-plus"></span> Create Ticket', ['create']) ?>
        </li>
        <li>
            <?= Html::a('<span class="glyphicon glyphicon-envelope"></span> Submit Ticket', ['update', 'mode' => 'submit']) ?>
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
        'rowOptions' => function($model) {
            return array_key_exists($model->state, $model->classMap) ? ['class' => $model->classMap[$model->state]] : null;
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'state',
                'format' => 'state',
                'filter' => array(
                    0 => yii::$app->formatter->format(0, 'state'),
                    1 => yii::$app->formatter->format(1, 'state'),
                    2 => yii::$app->formatter->format(2, 'state'),
                    3 => yii::$app->formatter->format(3, 'state'),
                    4 => yii::$app->formatter->format(4, 'state'),
                ),
            ],            
            'token',
            'examName',
            [
                'attribute' => 'examSubject',
                'filter' => $searchModel->subjectList,
            ],
            'start:timeago',
            'end:timeago',
            'valid:boolean',
            'test_taker',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete} {report}',
                'buttons' => [
                    'report' => function ($url) {
                        return Html::a('<span class="glyphicon glyphicon-save-file"></span>', $url,
                            [
                                'title' => 'Generate PDF Report',
                                'data-pjax' => '0',
                            ]
                        );
                    },
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    if ($action === 'report') {
                        return Url::toRoute(['ticket/report', 'id' => $model->id]);
                    }
                    return Url::toRoute(['ticket/' . $action, 'id' => $model->id]);
                },
            ],
        ],
    ]); ?>

    <?= $this->render('@app/views/_notification', [
        'session' => $session,
    ]) ?>

    <?php Pjax::end(); ?>

</div>
