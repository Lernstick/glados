<?php

namespace app\models;

use Yii;
use app\components\ElasticsearchBehavior;

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
 * @property array $restoreLog contains the restore log file line by line
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
    public function behaviors()
    {
        return [
            'ElasticsearchBehavior' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => self::tableName(),
                // what the attributes mean
                'fields' => [
                    'startedAt',
                    'finishedAt',
                    'restoreDate',
                    'file',
                    'restoreLog',
                    'ticket' => ['trigger_attributes' => ['ticket_id'], 'value_from' => 'ticket_id'],
                    'exam' => function($m){ return $m->ticket->exam_id; },
                    'user' => function($m){ return $m->ticket->exam->user_id; },
                ],
                // mapping of elasticsearch
                'properties' => [
                    'startedAt'   => ['type' => 'text'], // @todo: change to date
                    'finishedAt'  => ['type' => 'text'], // @todo: change to date
                    'restoreDate' => ['type' => 'text'], // @todo: change to date
                    'file'        => ['type' => 'text'],
                    'restoreLog'  => ['type' => 'text'],
                    'ticket'      => ['type' => 'integer'],
                    'exam'        => ['type' => 'integer'],
                    'user'        => ['type' => 'integer'],
                ]
            ],
        ];
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
            'id' => \Yii::t('restores', 'ID'),
            'startedAt' => \Yii::t('restores', 'Started At'),
            'finishedAt' => \Yii::t('restores', 'Finished At'),
            'ticket_id' => \Yii::t('restores', 'Ticket ID'),
            'file' => \Yii::t('restores', 'File'),
            'restoreDate' => \Yii::t('restores', 'Restore Version'),
        ];
    }

    /**
     * Getter for the restore log
     *
     * @return array
     */
    public function getRestoreLog()
    {
        $logFile = Yii::getAlias('@runtime/logs/restore.' . $this->ticket->token . '.' . date('c', strtotime($this->startedAt)) . '.log');
        if (file_exists($logFile)) {
            return file($logFile);
        }
        return [];
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
