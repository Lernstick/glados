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
class Activity extends Base
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
    public $test;

    /* may be removed */
    private $_description;

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
     * TODO: may not be necessary
     * @return void
     */
    public function setDescription($value)
    {
        $this->_description = $value;
    }

    /**
     * For each translated db field, such a function must be created, named getTranslationName()
     * returning the relation to the translation table
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTranslationDesciption()
    {
        return $this->hasOne(Translation::className(), ['id' => 'description_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTranslationTest()
    {
        return $this->hasOne(Translation::className(), ['id' => 'test_id']);
    }

    /**
     * For each translated db field, such a function must be created, named getTr_name()
     * returning the language row with data in it.
     *
     * @return string content of the row from the table corresponding to the language
     */
    public function getTr_description()
    {
        return \Yii::t(null, $this->description, $this->params, 'xxx');
    }

    /**
     * @inheritdoc
     */
    public function joinTables()
    {
        return [
            Ticket::tableName(),
            "translationDesciption description",
            "translationTest test"
        ];
    }

    public function insertDescription()
    {
        $keys = array_keys($this->params);
        $vals = array_map(function ($e) {
            return '{' . $e . '}';
        }, $keys);
        $params = array_combine($keys, $vals);

        // TODO: remove description_old
        //$this->description_old = $this->description;

        $tr = Translation::find()->where([
            'en' => \Yii::t('activities', $this->description, $params, 'en')
        ])->one();
        
        if ($tr === null || $tr === false) {
            // TODO: loop through all languages
            $translation = new Translation([
                'en' => \Yii::t('activities', $this->description, $params, 'en'),
                'de' => \Yii::t('activities', $this->description, $params, 'de'),
            ]);
            $translation->save();
            $this->description_id = $translation->id;
        } else {
            $this->description_id = $tr->id;
        }
        
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

    /**
     * Getter for the data. Returns the data or an empty array if there is
     * no data.
     * 
     * @return array
     */
    public function getParams()
    {
        return $this->data === null ? [] : Json::decode($this->data);
    }

    /**
     * Setter for the data. Format is as follows:
     * @see https://www.yiiframework.com/doc/guide/2.0/en/tutorial-i18n#message-parameters
     *
     *  [
     *      'key_1' => 'value_1'
     *      'key_2' => 'value_2'
     *      ...
     *      'key_n' => 'value_n'
     *  ]
     *
     * @return void
     */
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
        $query->joinWith(Activity::joinTables());
        $query->addSelect([
            '`activity`.*',
            // first the end-user language, then english (en) as fallback
            new \yii\db\Expression('COALESCE(NULLIF(`description`.`' . $c . '`, ""), NULLIF(`description`.`en`, ""), "") as description'),
            new \yii\db\Expression('COALESCE(NULLIF(`test`.`' . $c . '`, ""), NULLIF(`test`.`en`, ""), "") as test')
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
