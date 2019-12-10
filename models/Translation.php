<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "Translation".
 *
 * @property integer $id
 * @property string $en English
 * @property string $de German
 */
class Translation extends Base
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'translation';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'en' => 'English',
            'de' => 'German',
        ];
    }
}
