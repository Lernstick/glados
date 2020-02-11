<?php

/* @var $model mixed the data model */
/* @var $key mixed the key value associated with the data item */
/* @var $index integer the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget ListView this widget instance */

?>

<div class="row col-md-12"><?= yii::$app->formatter->format($model->value, $model->detail->type); ?></div>
<?php

if (!empty($model->members)) {
    echo '<table class="table table-bordered table-hover">';
    foreach ($model->members as $_model) {
        echo '<tr><td class="col-md-6">' . $_model->detail->name . '</td>';
        echo '<td class="col-md-6">' . yii::$app->formatter->format($_model->value, $_model->detail->type) . '</td></tr>';
    }
    echo '</table>';
}

?>