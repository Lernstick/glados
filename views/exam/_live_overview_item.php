<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\ActiveEventField;

/* @var $model app\models\Backup the data model */
/* @var $key integer mixed, the key value associated with the data item */
/* @var $index integer integer, the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget yii\widgets\ListView this widget instance */

?>

<div class="live-overview-item">
        <?= ActiveEventField::widget([
            'options' => [
                'tag' => 'img',
                'alt' => \Yii::t('ticket', 'Please wait, while the live image is being produced...'),
                'src' => Url::to(['ticket/live', 'token' => $model->token, '_ts']),
                'data-time' => intval(microtime(true)),
                'data-url' => Url::to(['ticket/live', 'token' => $model->token]),
                'data-toggle' => 'modal',
                'data-target' => '#galleryModal',
                'data-src' => Url::to(['screenshot/snap', 'token' => $model->token]),
                'data-alt' => Url::to(['ticket/live', 'token' => $model->token]),
            ],
            'event' => 'ticket/' . $model->id,
            'jsonSelector' => 'live',
            'jsHandler' => 'function(d, s) {
                // reload image when a new has arrived
                s.src = "data:image/jpg;base64," + d.base64;
                $(s).attr("data-time", parseInt(new Date().getTime()/1000));
                $(s).next().find(">:first-child").addClass("live");
                $(s).next().find(">:first-child").attr("title", "' . \Yii::t('ticket', 'Currently playing live') . '");
            }',
        ]); ?>

        <?= Html::a("<span class='glyphicon glyphicon-circle' title='" . \Yii::t('ticket', 'Currently behind live') . "'></span>" . (empty($model->test_taker) ? $model->token : $model->test_taker), Url::to(['ticket/view', 'id' => $model->id]), [
            'class' => 'live-overview-item-title'
        ]); ?>
        <span class='live-overview-fullscreen glyphicon glyphicon-fullscreen'></span>
</div>


