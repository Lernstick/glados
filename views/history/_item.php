<?php

/* @var $model app\models\History the data model */
/* @var $itemModel yii\base\Model the data model of the Item (for example: app\models\Ticket, app\models\Exam, app\models\User, ...) */
/* @var $key integer mixed, the key value associated with the data item */
/* @var $index integer integer, the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget yii\widgets\ListView this widget instance */

$format = $itemModel->getBehavior("HistoryBehavior")->formatOf($model->column);
$icon = $itemModel->getBehavior("HistoryBehavior")->iconOf($model);

?>

<div class="block">
    <div class="block-content">
        <h2 class="title">
            <?= $icon; ?>&nbsp;
            <?= $itemModel->getAttributeLabel($model->column); ?>
        </h2>
        <div class="byline">
            <span><?= yii::$app->formatter->format($model->changed_at, 'timeago'); ?></span> by <a><?= $model->userName; ?></a>
        </div>
        <div class="excerpt">
            <?= \Yii::t('history', 'The value has been changed from {old} to {new}.', [
                'old' => '«<b>' . yii::$app->formatter->format($model->old_value, $format) . '</b>»',
                'new' => '«<b>' . yii::$app->formatter->format($model->new_value, $format) . '</b>»',
            ]) ?>
        </div>
    </div>
</div>