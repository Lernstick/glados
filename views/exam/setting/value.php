<?php

/* @var $model mixed the data model */
/* @var $key mixed the key value associated with the data item */
/* @var $index integer the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget ListView this widget instance */

if (is_file(Yii::getAlias('@app/views/exam/setting/' . $model->key) . '.php')) {

    echo $this->render($model->key, [
        'model' => $model,
        'key' => $key,
        'index' => $index,
        'widget' => $widget,
    ]);

} else if (is_file(Yii::getAlias('@app/views/exam/setting/' . $model->detail->type) . '.php')) {

    echo $this->render($type, [
        'model' => $model,
        'key' => $key,
        'index' => $index,
        'widget' => $widget,
    ]);

} else {

    echo $this->render('default', [
        'model' => $model,
        'key' => $key,
        'index' => $index,
        'widget' => $widget,
    ]);

}

?>