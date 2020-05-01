<?php

use yii\helpers\Url;
use app\components\ActiveEventField;

/* @var $model app\models\Backup the data model */
/* @var $key integer mixed, the key value associated with the data item */
/* @var $index integer integer, the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget yii\widgets\ListView this widget instance */

?>

<div class="live-overview-item" data-alt="<?= Url::to(['ticket/live', 'token' => $model->token]) ?>" data-src="<?= Url::to(['screenshot/snap', 'token' => $model->token]) ?>" data-toggle="modal" data-target="#galleryModal",>
        <?= ActiveEventField::widget([
            'options' => [
                'tag' => 'img',
                'alt' => 'No img', #TODO
                'src' => Url::to(['ticket/live', 'token' => $model->token, '_ts']),
                'data-time' => intval(microtime(true)),
                'data-url' => Url::to(['ticket/live', 'token' => $model->token]),
                'class' => 'live-thumbnail',
            ],
            'event' => 'ticket/' . $model->id,
            'jsonSelector' => 'live',
            'jsHandler' => 'function(d, s) {
                // reload image when a new has arrived
                s.src = "data:image/jpg;base64," + d.base64;
                $(s).attr("data-time", parseInt(new Date().getTime()/1000));
                $(s).next().find(">:first-child").addClass("live");
            }',
        ]); ?>

        <span class="live-overview-item-title"><span>(live) </span><?= empty($model->test_taker) ? $model->token : $model->test_taker ?></span>
</div>


