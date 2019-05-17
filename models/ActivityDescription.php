<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "ActivityDescription".
 *
 * @property integer $id
 * @property string $en
 * @property string $de
 */
class ActivityDescription extends Base
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'description2';
    }
}
