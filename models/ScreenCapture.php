<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\HistoryBehavior;

/**
 * This is the model class for table "screen_capture".
 *
 * @property integer $id
 * @property integer $quality
 * @property string $command
 *
 * @property Exam $exam
 * 
 */
class ScreenCapture extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'screen_capture';
    }

    /**
     * @inheritdoc 
     */
    public function behaviors()
    {
        return [
            'HistoryBehavior' => [
                'class' => HistoryBehavior::className(),
                'relation' => ['exam', 'screen_capture_id'],
                'attributes' => [
                    'enabled' => 'boolean',
                    'quality' => 'text',
                    'command' => 'text',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc 
     */
    public function rules()
    {
        return [
            [['enabled'], 'boolean'],
            [['quality'], 'integer', 'min' => 0, 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('exams', 'ID'),
            'enabled' => \Yii::t('exams', 'Screen capture enabled'),
            'quality' => \Yii::t('exams', 'Quality'),
            'command' => \Yii::t('exams', 'Command'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'quality' => \Yii::t('exams', 'TODO'),
            'command' => \Yii::t('exams', 'TODO'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExam()
    {
        return $this->hasOne(Exam::className(), ['screen_capture_id' => 'id']);
    }
}