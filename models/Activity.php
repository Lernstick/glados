<?php

namespace app\models;

use Yii;
use yii\base\Event;
use app\models\Translation;
use app\models\EventItem;
use app\models\User;
use app\models\Ticket;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * This is the model class for table "activity".
 *
 * @property integer $id
 * @property string $date
 * @property string $description
 */
class Activity extends TranslatedActiveRecord
{

    /* activity severity constants */
    const SEVERITY_CRITICAL = 2;
    const SEVERITY_ERROR = 3;
    const SEVERITY_WARNING = 4;
    const SEVERITY_NOTICE = 5;
    const SEVERITY_INFORMATIONAL = 6;
    const SEVERITY_SUCCESS = 7;

    /* db translated fields */
    public $description;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'eventNewActivities']);

        parent::init();
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
    public function getTranslatedFields()
    {
        return [
            'description',
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
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            //[['description'], 'required'],
            //[['description'], 'string', 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('activity', 'ID'),
            'token' => \Yii::t('activity', 'Ticket'),
            'date' => \Yii::t('activity', 'Date'),
            'description' => \Yii::t('activity', 'Description'),
            'severity' => \Yii::t('activity', 'Severity'),
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
     * Mapping of the different severities and the color classes
     *
     * @return array
     */
    public function getClassMap()
    {
        return [
            self::SEVERITY_CRITICAL => "danger",
            self::SEVERITY_ERROR  => "danger",
            self::SEVERITY_WARNING => "warning",
            self::SEVERITY_NOTICE => "primary",
            self::SEVERITY_INFORMATIONAL => "info",
            self::SEVERITY_SUCCESS => "success",
            null => "default",
        ];
    }

    /**
     * Mapping of the different severities and names
     *
     * @return array
     */
    public function getNameMap()
    {
        return [
            self::SEVERITY_CRITICAL => "critical",
            self::SEVERITY_ERROR  => "error",
            self::SEVERITY_WARNING => "warning",
            self::SEVERITY_NOTICE => "notice",
            self::SEVERITY_INFORMATIONAL => "info",
            self::SEVERITY_SUCCESS => "success",
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
        
        $query = new ActivityQuery(get_called_class());
        $query->joinWith(Activity::joinTables());
        return $query;
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
