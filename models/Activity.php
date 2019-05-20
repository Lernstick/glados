<?php

namespace app\models;

use Yii;
use yii\base\Event;
use app\models\ActivityDescription;
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
class Activity extends Base
{

    /* activity severity constants */
    const SEVERITY_CRITICAL = 2;
    const SEVERITY_ERROR = 3;
    const SEVERITY_WARNING = 4;
    const SEVERITY_NOTICE = 5;
    const SEVERITY_INFORMATIONAL = 6;
    const SEVERITY_SUCCESS = 7;

    private $_description;
    public $description;

    /**
     * @inheritdoc
     */
    public function init()
    {
//        Event::on(ActiveRecord::className(), ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
//            file_put_contents('/tmp/file', '+1');
//        });
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'eventNewActivities']);
        $this->on(self::EVENT_BEFORE_INSERT, [$this, 'insertDescription']);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function setDescription($value)
    {
        $this->_description = $value;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function gettr_activity_description()
    {
        return $this->hasOne(ActivityDescription::className(), ['id' => 'description_id']);
    }

    /**
     * @inheritdoc
     */
    public function joinTables()
    {
        return [ Ticket::tableName(), ActivityDescription::tableName() . " description" ];
    }

    // TODO: loop through all languages
    public function insertDescription()
    {
        $this->description_old = $this->_description;
        $translation = new ActivityDescription([
            'en' => \Yii::t('activities', $this->_description, $this->params, 'en'),
            'de' => \Yii::t('activities', $this->_description, $this->params, 'de'),
        ]);
        $translation->save();
        $this->description_id = $translation->id;
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
            'id' => \Yii::t('activities', 'ID'),
            'token' => \Yii::t('activities', 'Ticket'),
            'date' => \Yii::t('activities', 'Date'),
            'description' => \Yii::t('activities', 'Description'),
            'severity' => \Yii::t('activities', 'Severity'),
        ];
    }

    public function getParams()
    {
        return Json::decode($this->data);
    }

    public function setParams($value)
    {
        $this->data = Json::encode($value);
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
        $c = \Yii::$app->language;
        $query = new ActivityQuery(get_called_class());
        $query->addSelect([
            '`activity`.*',
            new \yii\db\Expression('COALESCE(NULLIF(`description`.`' . $c . '`, ""), NULLIF(`description`.`en`, ""), "") as description')
        ]);

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
