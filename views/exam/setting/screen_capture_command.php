<?php

/* @var $model mixed the data model */
/* @var $key mixed the key value associated with the data item */
/* @var $index integer the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget ListView this widget instance */

?>

<code>
	<?= yii::$app->formatter->format($model->value, $model->detail->type) ?>
</code>
