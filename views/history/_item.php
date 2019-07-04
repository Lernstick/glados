<?php

use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\History;
use yii\data\ArrayDataProvider;

/* @var $model app\models\History the data model */
/* @var $itemModel yii\base\Model the data model of the Item (for example: app\models\Ticket, app\models\Exam, app\models\User, ...) */
/* @var $key integer mixed, the key value associated with the data item */
/* @var $index integer integer, the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget yii\widgets\ListView this widget instance */

$columns = explode($model::SEPARATOR, $model->columns);
$new_values = explode($model::SEPARATOR, $model->new_values);
$old_values = explode($model::SEPARATOR, $model->old_values);

$models = [];

foreach ($columns as $key => $column) {
    $models[] = new History([
        'changed_at' => $model->changed_at,
        'changed_by' => $model->changed_by,
        'new_value' => $new_values[$key],
        'old_value' => $old_values[$key],
        'column' => $columns[$key],
        'table' => $model->table,
        'row' => $model->row,
    ]);
}

$provider = new ArrayDataProvider([
    'allModels' => $models,
    'pagination' => [
        'pageSize' => 20,
        'pageParam' => 'hist-page-' . $key,
    ],
]);

?>

<div class="block">
    <div class="block-content">
        <h2 class="title">
            <div class="byline">
            <i class="glyphicon glyphicon-edit"></i>&nbsp;
            <?= \Yii::t('history', '{date} by <a>{user}</a>', [
                'date' => yii::$app->formatter->format($model->changed_at, 'timeago'),
                'user'=> $model->userName,
            ]); ?>
            </div>
        </h2>
        <div class="excerpt">

            <?php Pjax::begin() ?>

                <?= GridView::widget([
                    'dataProvider' => $provider,
                    'columns' => [
                        [
                            'attribute' => 'column',
                            'value' => function ($model) use ($itemModel) {
                                $icon = $itemModel->getBehavior("HistoryBehavior")->iconOf($model);
                                return $icon . '&nbsp;' . $itemModel->getAttributeLabel($model->column);
                            },
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'old_value',
                            'value' => function ($model) use ($itemModel) {
                                $format = $itemModel->getBehavior("HistoryBehavior")->formatOf($model->column);
                                return yii::$app->formatter->format($model->old_value, $format);
                            },
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'new_value',
                            'value' => function ($model) use ($itemModel) {
                                $format = $itemModel->getBehavior("HistoryBehavior")->formatOf($model->column);
                                return yii::$app->formatter->format($model->new_value, $format);
                            },
                            'format' => 'raw',
                        ],
                    ],
                    'layout' => '{items} {pager}',
                ]); ?>

            <?php Pjax::end() ?>
        </div>
    </div>
</div>