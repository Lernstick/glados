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
     *
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
     *
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
            return round($value, 1);
        }
    }

    /**
     * Returns the number of hours as shortNumber.
     *
     * @param integer $value the value to be formatted.
     * @return string the formatted result.
     */
    public static function asHours($value)
    {
        return yii::$app->formatter->format($value/3600, 'shortNumber');
    }

    /**
     * Uses Timeago to format relativetime to automatically update fuzzy timestamps.
     *
     * @param integer $value the value to be formatted.
     * @return string the formatted result.
     */
    public static function asTimeago($value)
    {
        if (empty($value)){
            $value = '<span class="not-set">(not set)</span>';
        } else {
            $value = \yii\timeago\TimeAgo::widget(['timestamp' => $value]);
        }
        return $value;
    }

    /**
     * Translates dates such as 'now' and 'all' correctly.
     *
     * @param integer $value the value to be formatted.
     * @return string the formatted result.
     */
    public static function asBackupVersion($value)
    {
        if (empty($value)){
            $value = '<span class="not-set">(not set)</span>';
        } else if ($value == 'now') {
            $value = 'current';
        } else if ($value == 'all') {
            $value = 'all';
        } else {
            $value = yii::$app->formatter->format($value, 'datetime');
        }
        return $value;
    }

}

