<?php

/* @var $model mixed the data model */
/* @var $key mixed the key value associated with the data item */
/* @var $index integer the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget ListView this widget instance */

if (is_file(Yii::getAlias('@app/views/exam/setting/' . $model->key) . '.php')) {
    $view = $model->key;
} else if (is_file(Yii::getAlias('@app/views/exam/setting/' . $model->detail->type) . '.php')) {
    $view = $model->detail->type;
} else {
    $view = 'default';
}

if (!empty($model->members) && $view !== $model->key) {
    echo '<table class="table table-bordered table-hover">';
    echo '<tr><td colspan="2">';
}

echo $this->render($view, [
    'model' => $model,
    'key' => $key,
    'index' => $index,
    'widget' => $widget,
]);

if (!empty($model->members) && $view !== $model->key) {
    echo '</td></tr>';

    foreach ($model->members as $_model) {
        echo '<tr><td class="col-md-3">' . $_model->detail->name . '</td>';
        echo '<td class="col-md-9">';
        echo $this->render('value', [
            'model' => $_model,
            'key' => $key,
            'index' => $index,
            'widget' => $widget,
        ]);
        echo '</td></tr>';
    }

    echo '</table>';
}

?>