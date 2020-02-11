<?php

/* @var $model mixed the data model */
/* @var $key mixed the key value associated with the data item */
/* @var $index integer the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget ListView this widget instance */

foreach($model->members as $s) {
    if ($s->key == 'libre_autosave_interval') {
        $libre_autosave_interval = $s;
    } else if ($s->key == 'libre_autosave_path') {
        $libre_autosave_path = $s;
    }
}

echo \Yii::t('exams', '<b>{yesno}</b> (to <code>{path}</code> every <i>{interval}</i>)', [
	'yesno' => yii::$app->formatter->format($model->value, 'boolean'),
    'path' => yii::$app->formatter->format($libre_autosave_path->value, 'text'),
    'interval' => yii::$app->formatter->format($libre_autosave_interval->value*60, 'duration'),
]);

?>
