<?php

namespace app\models;

use Yii;
use yii\helpers\Markdown;
use yii\helpers\ArrayHelper;
use app\models\ExamSettingAvail;

/**
 * This is the model class for table "exam_setting".
 *
 * @inheritdoc
 */
class ExamSetting extends Base
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'exam_setting';
    }


    /**
     * @inheritdoc 
     */
    public function rules()
    {
        $rules = [
            [['value', 'key'], 'safe'],
        ];

        // create row-wise rules according to rulesByKey()
        foreach ($this->rulesByKey() as $i => $rule) {
            if ($this->key == $rule[0]) {
                $append = [
                    'value',
                    $rule[1],
                ];
                foreach ($rule as $key => $value) {
                    if (!is_int($key)) {
                        $append[$key] = $value;
                    }
                }
                $rules[] = $append;
            }
        }

        return $rules;
    }

    /**
     * A set of rules by key
     * @return array rules
     */
    public function rulesByKey()
    {
        return [
            ['screenCaptureEnabled', 'boolean'],
            ['screenCaptureQuality', 'integer', 'min' => 1, 'max' => 100],
            ['libre_autosave', 'integer', 'min' => 1, 'max' => 100],
            ['libre_autosave', 'required'],
            ['max_brightness', 'integer', 'min' => 1, 'max' => 100],
            ['max_brightness', 'required'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDetail()
    {
        return $this->hasOne(ExamSettingAvail::className(), ['key' => 'key']);
    }

}
