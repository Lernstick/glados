<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\data\ArrayDataProvider;

/* @var $model app\models\Role */
/* @var $key midex the key value associated with the data item */
/* @var $index integer the zero-based index of the data item in the items array returned by $dataProvider */
/* @var $widget ListView this widget instance */

?>

<tr>
    <td>
        <?= substitute('{description} ({name})', [
            'description' => Yii::t('permission', $model->description),
            'name' => $model->type === $model::TYPE ? Html::a(
                $model->name,
                Url::to(['role/view', 'id' => $model->name])
            ) : $model->name,
        ]) ?>
    </td>
</tr>

<?php

if (count($model->childrenObjects) > 0) {

    echo ListView::widget([
        'dataProvider' => new ArrayDataProvider([
            'allModels' => $model->childrenObjects,
            'pagination' => [
                'pageSize' => -1,
            ],
        ]),
        'itemView' => '_permission',
        'itemOptions' => ['tag' => false],
        'options' => ['tag' => false],
        'layout' => '<tr><td><table class="table table-bordered table-hover">{items}</table></td></tr>',
    ]);

}

?>
