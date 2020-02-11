<?php

/* @var $model mixed the data model */
/* @var $key mixed the key value associated with the data item */
/* @var $index integer the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget ListView this widget instance */

foreach($model->members as $s) {
    if ($s->key == 'libre_createbackup_path') {
        $libre_createbackup_path = $s;
    }
}

echo \Yii::t('exams', '<b>{yesno}</b> (to <code>{path}</code>)', [
	'yesno' => yii::$app->formatter->format($model->value, 'boolean'),
    'path' => yii::$app->formatter->format($libre_createbackup_path->value, 'text'),
]);

?>
