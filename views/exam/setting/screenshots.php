<?php

/* @var $model mixed the data model */
/* @var $key mixed the key value associated with the data item */
/* @var $index integer the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget ListView this widget instance */

foreach($model->members as $s) {
    if ($s->key == 'screenshots_interval') {
        $screenshots_interval = $s;
    }
}

if ($model->value) {
    echo \Yii::t('exams', '<b>{yesno}</b> (every <i>{interval}</i>)', [
        'yesno' => yii::$app->formatter->format($model->value, 'boolean'),
        'interval' => yii::$app->formatter->format($screenshots_interval->value*60, 'duration'),
    ]);
} else {
    echo \Yii::t('exams', '<b>{yesno}</b>', [
        'yesno' => yii::$app->formatter->format($model->value, 'boolean'),
    ]);
}

?>
