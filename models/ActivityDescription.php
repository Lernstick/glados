<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "ActivityDescription".
 *
 * @property integer $id
 * @property string $en English
 * @property string $de German
 */
class ActivityDescription extends Base
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tr_activity_description';
    }

    /**
     * @inheritdoc
     *
     * TODO: fallback to other languages
     */
    public function __toString()
    {
        return $this->{\Yii::$app->language};
    }

}
