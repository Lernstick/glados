<?php

namespace app\components;
 
use Yii;
use yii\i18n\Formatter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException;
 
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
            case 0:  return Yii::t('ticket', 'Open', $params);
            case 1:  return Yii::t('ticket', 'Running', $params);
            case 2:  return Yii::t('ticket', 'Closed', $params);
            case 3:  return Yii::t('ticket', 'Submitted', $params);
            case 4:  return Yii::t('ticket', 'Unknown', $params);
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
            $value = Html::tag('time', \Yii::t('app', '(not set)'), [
                'class' => 'not-set',
                'datetime' => '',
                'data-invalid' => \Yii::t('app', '(invalid date)'),
                'data-notset' => \Yii::t('app', '(not set)'),
                'title' => '',
            ]);
        } else {
            try {
                $value = \yii\timeago\TimeAgo::widget([
                    'timestamp' => $value,
                    'options' =>  [
                        'data-invalid' => \Yii::t('app', '(invalid date)'),
                        'data-notset' => \Yii::t('app', '(not set)'),
                    ],
                ]);
            }
            // just catch the error
            catch (InvalidArgumentException $e) {
                $value = Html::tag('time', \Yii::t('app', '(invalid date)'), [
                    'class' => 'invalid',
                    'datetime' => '',
                    'data-invalid' => \Yii::t('app', '(invalid date)'),
                    'data-notset' => \Yii::t('app', '(not set)'),
                    'title' => $value,
                ]);
            }
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
            $value = '<span class="not-set">' . \Yii::t('app', '(not set)') . '</span>';
        } else if ($value == 'now') {
            $value = 'current';
        } else if ($value == 'all') {
            $value = 'all';
        } else {
            $value = yii::$app->formatter->format($value, 'timeago');
        }
        return $value;
    }

    /**
     * Formats the time limit as duration or a custom string.
     *
     * @param integer $value the value to be formatted in minutes.
     * @return string the formatted result.
     */
    public static function asTimeLimit($value)
    {
        if (is_int($value) && $value > 0) {
            $value = yii::$app->formatter->format($value*60, 'duration');
        } else {
            $value = \Yii::t('ticket', 'No Time Limit');
        }
        return $value;
    }

    /**
     * Formats the an associative array as table.
     *
     * @param array array the value to be formatted as assiciative array.
     * @param array heading the table heading of the gridview (array with 2 elements)
     * @return string the formatted result.
     */
    public static function asMapping($array, $header = ['from', 'to'])
    {

        $models = array_map(function($k, $v) use ($header) {
            return array(
                $header[0] => $k,
                $header[1] => $v
            );
        }, array_keys($array), array_values($array));

        $dataProvider = new \yii\data\ArrayDataProvider([
            'allModels' => $models,
        ]);
        $dataProvider->pagination->pageParam = 'map-page';
        $dataProvider->pagination->pageSize = 10;

        return \yii\grid\GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => $header[0],
                    'label' => $header[0],
                ],
                [
                    'attribute' => $header[1],
                    'label' => $header[1],
                ],
            ],
            'layout' => '{items} {pager}',
        ]);
    }

    /**
     * Formats the links in the input as actual links.
     * 
     * links are given by {url:{name}:{controller}:{action}:variable1={value1},variable2={value2}}
     * they becomes:
     * Html::a('{name}', Url::to('{controller}/{action}', [
     *     'variable1' => '{value1}',
     *     'variable2' => '{value2}',
     * ]));
     *
     * @param integer $value the value to be formatted in minutes.
     * @return string the formatted result.
     */
    public static function asLinks($value)
    {
        return preg_replace_callback('/\{url\:([^\:]+)\:([^\:]+)\:([^\:]+)\:([^\}]*)\}/', function($m){
            $params = [];
            $p = explode(',', $m[4]);
            foreach ($p as $param) {
                list($key, $val) = explode('=', $param, 2);
                $params[$key] = $val;
            }
            $params[0] = $m[2].'/'.$m[3];
            return Html::a($m[1], Url::to($params), ['data-pjax' => 0]);
        }, $value);
    }

}

