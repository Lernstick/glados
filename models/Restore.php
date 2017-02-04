<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "restore".
 *
 * @property integer $id
 * @property string $startedAt
 * @property string $finishedAt
 * @property integer $ticket_id
 * @property string $file
 * @property string $restoreDate
 * @property DateInterval $elapsedTime 
 *
 * @property Ticket $ticket
 */
class Restore extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'restore';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['startedAt', 'finishedAt', 'restoreDate'], 'safe'],
            [['ticket_id', 'file'], 'required'],
            [['ticket_id'], 'integer'],
            [['file'], 'string', 'max' => 254],
            [['ticket_id'], 'exist', 'skipOnError' => true, 'targetClass' => Ticket::className(), 'targetAttribute' => ['ticket_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'startedAt' => 'Started At',
            'finishedAt' => 'Finished At',
            'ticket_id' => 'Ticket ID',
            'file' => 'File',
            'restoreDate' => 'Restore Date',
        ];
    }

    /**
     * @return DateInterval
     */
    public function getElapsedTime()
    {
        $a = new \DateTime($this->startedAt);
        $b = new \DateTime($this->finishedAt);

        return $a->diff($b);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTicket()
    {
        return $this->hasOne(Ticket::className(), ['id' => 'ticket_id']);
    }
}
