<?php

use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\History;
use app\models\Translation;
use yii\data\ArrayDataProvider;
use yii\helpers\Json;


/* @var $model app\models\History the data model */
/* @var $itemModel yii\base\Model the data model of the Item (for example: app\models\Ticket, app\models\Exam, app\models\User, ...) */
/* @var $searchModel app\models\HistorySearch the search model */
/* @var $key integer mixed, the key value associated with the data item */
/* @var $index integer integer, the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget yii\widgets\ListView this widget instance */


$models = [];

foreach ($model->columns as $key => $column) {

    $parts = explode('_', $column);
    $last = array_pop($parts);
    $pname = implode('_', $parts);

    if ($itemModel->hasMethod('getTranslatedFields')
        && in_array($pname, $itemModel->translatedFields)
    ) {

        if ($last == 'id') {
            $new = Translation::findOne($model->new_values[$key]);
            $old = Translation::findOne($model->old_values[$key]);
            $new_params = [];
            $old_params = [];
            if ( ($k = array_search($pname . '_data', $model->columns)) !== false) {
                $new_params = Json::decode($model->new_values[$k]);
                $old_params = Json::decode($model->old_values[$k]);
            } else {
                $previous = History::find()->where([
                    'table' => $itemModel->tableName(),
                    'row' => $itemModel->id,
                    'column' => $pname . '_data',
                ])->orderBy(['changed_at' => SORT_DESC])->one();
                if ($previous !== null) {
                    $new_params = Json::decode($previous->new_value);
                    $old_params = Json::decode($previous->new_value);
                }
            }

            $models[] = new History([
                'changed_at' => $model->changed_at,
                'changed_by' => $model->changed_by,
                'new_value' => \Yii::t($model->table, $new['en'], $new_params),
                'old_value' => \Yii::t($model->table, $old['en'], $old_params),
                'column' => $pname,
                'table' => $model->table,
                'row' => $model->row,
            ]);
        } else if ($last == 'data') {
            continue;
        }

    } else {
        $models[] = new History([
            'changed_at' => $model->changed_at,
            'changed_by' => $model->changed_by,
            'new_value' => $model->new_values[$key],
            'old_value' => $model->old_values[$key],
            'column' => $model->columns[$key],
            'table' => $model->table,
            'row' => $model->row,
        ]);
    }
}

$provider = new ArrayDataProvider([
    'allModels' => $models,
    'pagination' => [
        'pageSize' => 20,
        'pageParam' => 'hist-page-' . $key,
    ],
]);

$model->searchColumn = $searchModel->column;

?>

<div class="block">
    <div class="block-content">
        <h2 class="title">
            <i class="glyphicon glyphicon-edit"></i>&nbsp;
            <?= \Yii::t('history', '{date} by <a>{user}</a>', [
                'date' => yii::$app->formatter->format($model->changed_at, 'timeago'),
                'user'=> $model->userName,
            ]); ?>
        </h2>
        <div class="byline">

            <?= $model->diffToLast == -1
                ? \Yii::t('history', 'This is the first modification since creation')
                : \Yii::t('history', '{duration} since last modification', [
                    'duration' => yii::$app->formatter->format($model->diffToLast, 'duration'),
                ]);
            ?>
        </div>
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
                            'attribute' => 'new_value',
                            'value' => function ($model) use ($itemModel) {
                                $format = $itemModel->getBehavior("HistoryBehavior")->formatOf($model->column);
                                return yii::$app->formatter->format($model->new_value, $format);
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
                    ],
                    'layout' => '{items} {pager}',
                ]); ?>

            <?php Pjax::end() ?>
        </div>
    </div>
</div>