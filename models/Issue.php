<?php

namespace app\models;

use Yii;
use yii\db\Expression;

/**
 * This is the model class for table "issue".
 *
 * @property integer $id
 * @property string $ticket_id
 * @property Ticket $ticket
 */
class Issue extends Base
{

    /* issue constants */
    const CLIENT_OFFLINE = 0;
    const LONG_TIME_NO_BACKUP = 1;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'newIssue']);
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'newIssue']);
        parent::init();
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'issue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['id', 'unique', 'targetAttribute' => ['id', 'ticket_id']],
            ['key', 'integer', 'min' => 0, 'max' => 999],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'key' => \Yii::t('issues', 'Issue'),
            'occuredAt' => \Yii::t('issues', 'Since'),
            'solvedAt' => \Yii::t('issues', 'Solved At'),
            'ticket.token' => \Yii::t('issues', 'Ticket Token'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function joinTables()
    {
        return [
            Ticket::tableName(),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTicket()
    {
        return $this->hasOne(Ticket::className(), ['id' => 'ticket_id']);
    }

    /* Getter for exam name */
    public function getTicket_state()
    {
        return $this->ticket->state;
    }

    /**
     * Add the issue if not yet added.
     * @param $type int one of the issue contants
     * @param $ticket_id int the id of the associated ticket
     * @return bool
     */
    public static function markAs($type, $ticket_id)
    {
        $issue = Issue::findOne([
            'key' => $type,
            'solvedAt' => null,
            'ticket_id' => $ticket_id
        ]);

        if ($issue === null) {
            $issue = new Issue([
                'key' => $type,
                'ticket_id' => $ticket_id,
            ]);
            return $issue->save();
        } else {
            return true;
        }
    }

    /**
     * Mark the issue as solved.
     * @param $type int one of the issue contants
     * @param $ticket_id int the id of the associated ticket
     * @return bool
     */
    public static function markAsSolved($type, $ticket_id)
    {
        $issue = Issue::findOne([
            'key' => $type,
            'solvedAt' => null,
            'ticket_id' => $ticket_id
        ]);

        if ($issue !== null) {
            $issue->solvedAt = new Expression('NOW()');
            return $issue->save();
        } else {
            return true;
        }
    }


    /**
     * Triggers the reload of the active issues in the live view.
     * 
     * @return void
     */
    public function newIssue()
    {
        $eventItem = new EventItem([
            'event' => 'exam/' . $this->ticket->exam->id,
            'priority' => 0,
            'concerns' => $this->ticket->concerns,
            'data' => ['newIssue' => $this->key],
        ]);
        return $eventItem->generate();
    }
}