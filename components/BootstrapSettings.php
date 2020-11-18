<?php

namespace app\components;

use yii\base\BootstrapInterface;
use app\models\Setting;
use yii\helpers\ArrayHelper;
use \yii\db\Query;

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
        /**
         * Merge the default settings with the settings given in params.php
         * @param array $defaultParams default value if nothing is set
         * @param array $params the values from the config file
         */
        $paramsFromFile = ArrayHelper::merge($this->defaultParams, $this->params);

        try {
            $settings = Setting::find()->all();
            // values from the DB
            $paramsFromDb = ArrayHelper::map($settings, 'key', function($model) {
                return Setting::renderSetting($model->value !== null ? $model->value : $model->default_value, $model->type);
            });
            // Merge the settings with the settings given in the database
            $app->params = ArrayHelper::merge($paramsFromFile, $paramsFromDb);
        }
        catch (\yii\db\Exception $e) {
            return;
        }
        # to also catch the error even if the table "setting" does not exist yet
        # for backwards compatibility
        catch (\yii\base\InvalidConfigException $e) {
            return;
        }

    }
}