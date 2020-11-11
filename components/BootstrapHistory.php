<?php

namespace app\components;

use yii\base\BootstrapInterface;

/**
 * BootstrapHistory implements BootstrapInterface.
 *
 * Attaches the behavior to the Connection model
 */
class BootstrapHistory implements BootstrapInterface
{
    public function bootstrap($app)
    {
        \Yii::$app->db->attachBehavior('history', 'app\components\HistoryBehavior');
    }
}