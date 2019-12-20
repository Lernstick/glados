<?php

namespace app\components;

use yii\base\BootstrapInterface;
use app\models\Setting;
use yii\helpers\ArrayHelper;

/**
 * BootstrapSettings implements BootstrapInterface.
 *
 * Determine all settings from the DB and the params.php file
 */
class BootstrapSettings implements BootstrapInterface
{
    public $params = [];

    public function bootstrap($app)
    {
        $paramsFromFile = $this->params;
        $settings = Setting::find()->all();
        $paramsFromDb = ArrayHelper::map($settings, 'key', function($model) {
            return Setting::renderSetting($model->value != null ? $model->value : $model->default_value, $model->type);
        });
        $app->params = ArrayHelper::merge($paramsFromFile, $paramsFromDb);
    }
}