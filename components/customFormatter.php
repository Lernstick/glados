<?php

namespace app\components;
 
use Yii;
use yii\i18n\Formatter;
use yii\base\InvalidConfigException;
 
class customFormatter extends \yii\i18n\Formatter
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Formats the value as one of the 4 states or unknown if no state can be determined.
     * @param integer $value the value to be formatted.
     * @return string the formatted result.
     */
    public static function asState($value)
    {
        $params = [];

        switch ($value) {
            case 0:  return Yii::t('yii', 'Open', $params);
            case 1:  return Yii::t('yii', 'Running', $params);
            case 2:  return Yii::t('yii', 'Closed', $params);
            case 3:  return Yii::t('yii', 'Submitted', $params);
            case 4:  return Yii::t('yii', 'Unknown', $params);
            default: return $value;
        }
    }

    /**
     * Shortens the number and append a "k" for thousands and an "m" for millions.
     * @param integer $value the value to be formatted.
     * @return string the formatted result.
     */
    public static function asShortNumber($value)
    {
        if ($value >= 9500000) {
            return round($value / 1000000, 0) . "m";
        } else if ($value >= 950000) {
            return round($value / 1000000, 1) . "m";
        } else if ($value >= 95000) {
            return round($value / 1000, 0) . "k";
        } else if ($value >= 950) {
            return round($value / 1000, 1) . "k";
        } else {
            return $value;
        }
    }
}

