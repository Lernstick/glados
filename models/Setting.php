<?php

namespace app\models;

use Yii;
use yii\helpers\Markdown;
use yii\helpers\ArrayHelper;
use app\models\LiveActiveRecord;

/**
 * This is the model class for table "setting".
 *
 * @inheritdoc
 */
class Setting extends SettingBase
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'setting';
    }

    /**
     * A set of rules by key
     * @return array rules
     */
    public function rulesByKey()
    {
        return [
            ['tokenLength', 'integer', 'min' => 4, 'max' => 16],
            ['tokenLength', 'required'],
            ['tokenChars', 'string', 'min' => 10, 'max' => 62],
            ['tokenChars', 'match',
                'pattern' => '/^(?:(.)(?!.*?\1))+$/', // only distict characters
                'message' => \Yii::t('setting', 'Duplicate character found. All characters must be unique.')
            ],
            ['tokenChars', 'match',
                'pattern' => '/^[0-9a-zA-Z]+$/', // only alphanumeric characters
                'message' => \Yii::t('setting', 'Only alphanumeric characters (0-9, a-z, A-Z) are allowed.')
            ],
            ['tokenChars', 'required'],
            ['loginHint', 'string', 'min' => 0, 'max' => 1024],
            ['upperBound', 'required'],
            ['upperBound', 'integer', 'min' => 30, 'max' => 100],
            ['lowerBound', 'required'],
            ['lowerBound', 'integer', 'min' => 10, 'max' => 100],
            ['minDaemons', 'required'],
            ['minDaemons', 'integer', 'min' => 1, 'max' => 100],
            ['maxDaemons', 'required'],
            ['maxDaemons', 'integer', 'min' => 1, 'max' => 100],
            ['abandonTicket', 'required'],
            ['abandonTicket', 'integer', 'min' => 1800, 'max' => 36000],
            ['monitorInterval', 'required'],
            ['monitorInterval', 'integer', 'min' => 1, 'max' => 100],
        ];
    }
}
