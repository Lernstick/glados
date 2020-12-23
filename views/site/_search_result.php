<?php

use yii\helpers\Url;
use yii\helpers\Html;

/* @var $model ????? the data model */
/* @var $widget yii\widgets\ListView this widget instance */

?>

<?= Html::a($model['_index'] . '/' . $model['_id'], Url::to([$model['_index'] . '/view', 'id' => $model['_id']])); ?>
<?= ", score=" . $model['_score']; ?>
<br>
<?php

if (array_key_exists('highlight', $model)) {
    foreach ($model['highlight'] as $field => $values) {
        echo "<b>" . $field . "</b>: ";
        foreach ($values as $value) {
            echo $value . ", ";
        }
    }
    echo "<br>";
}

?>
<pre><?= var_dump($model); ?></pre>
<hr>
