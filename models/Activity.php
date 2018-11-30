<?php

namespace app\models;

use Yii;
use yii\base\Event;
use app\models\EventItem;
use app\models\User;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "activity".
 *
 * @property integer $id
 * @property string $date
 * @property string $description
 */
class Activity extends Base
{

        /**
     * @inheritdoc
     */
    public function init()
    {
//        Event::on(ActiveRecord::className(), ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
//            file_put_contents('/tmp/file', '+1');
//        });
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'eventNewActivities']);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['description'], 'required'],
            [['description'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'token' => 'Ticket',
            'date' => 'Date',
            'description' => 'Description',
        ];
    }

    public function newActivities()
    {

        $lastvisited = $this->lastvisited;

        $query = $this->find()->where(['>', 'date', $lastvisited]);
        Yii::$app->user->can('activity/index/all') ?: $query->own();

        $new = $query->count();

        if($new == 0){
            return '';
        }
        return $new; 
    }

    public function eventNewActivities()
    {

        $event = new EventItem([
            'event' => 'newActivities',
            'priority' => 1,
            'data' => [
                'newActivities' => '+1'
            ],
        ]);
        $event->generate();

        $event = new EventItem([
            'event' => 'ticket/' . $this->ticket->id,
            'priority' => 1,
            'data' => [
                'newActivities' => '+1',
            ],
        ]);
        $event->generate();

    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTicket()
    {
        return $this->hasOne(Ticket::className(), ['id' => 'ticket_id']);
    }

    /* Getter for exam name */
    public function getToken()
    {
        return $this->ticket->id;
    }

    /** 
     * @inheritdoc 
     * @return ActivityQuery the active query used by this AR class. 
     */ 
    public static function find() 
    { 
        return new ActivityQuery(get_called_class()); 
    } 


    public function getLastvisited()
    {
        /*$getcookies = Yii::$app->request->cookies;
        $lastvisited = $getcookies->getValue('lastvisited', date_format(new \DateTime('now'), 'Y-m-d H:i:s'));
        return $lastvisited;*/
        if (($user = User::findOne(\Yii::$app->user->id)) !== null) {
            return $user->activities_last_visited;
            return $user->activities_last_visited === null ? 
                date_format(new \DateTime('0001-01-01'), 'Y-m-d H:i:s') : 
                $user->activities_last_visited;
        }
        return null;
    }

    public function setLastvisited($value)
    {
        $user = User::findOne(\Yii::$app->user->id);
        $user->scenario = 'update';
        $user->activities_last_visited = date_format(new \DateTime($value), 'Y-m-d H:i:s');
        $user->save();
    }

}
