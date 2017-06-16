<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ConflictHttpException;
use yii\base\Event;
use app\models\Backup;
use app\models\Restore;
use app\models\EventItem;

/**
 * This is the model class for table "ticket".
 *
 * @property integer $id
 * @property string $token
 * @property integer $exam_id
 * @property timestamp $start
 * @property timestamp $end
 * @property string $ip 
 * @property string $test_taker
 * @property integer $download_progress
 * @property boolean $download_lock
 * @property string $client_state
 * @property timestamp $backup_last
 * @property timestamp $backup_last_try
 * @property string $backup_state
 * @property boolean $backup_lock 
 * @property integer $running_daemon_id
 * @property boolean $restore_lock
 * @property string $restore_state
 * @property boolean $bootup_lock
 *
 * @property timestamp $startTime
 * @property array $classMap
 * @property boolean $valid
 * @property boolean $backup
 * @property Backup[] $backups
 * @property integer $state
 * @property DateInterval $duration 
 * @property string $examName
 * @property string $examSubject
 * @property integer $userId
 *
 * @property Exam $exam
 * @property Activity[] $activities
 * @property Restore[] $restores
 * @property Exam $exam
 * @property Exam $exam
 */
class Ticket extends \yii\db\ActiveRecord
{

    /**
     * @var integer A value from 0 to 4 representing the state of the ticket.
     * @see ticket state constants below.
     */
    public $state;

    public $tduration;

    /**
     * @var array An array holding the values of the record before changing
     */
    private $presaveAttributes;

    /* scenario constants */
    const SCENARIO_DEFAULT = 'default';
    const SCENARIO_SUBMIT = 'submit';
    const SCENARIO_DOWNLOAD = 'download';
    const SCENARIO_FINISH = 'finish';
    const SCENARIO_NOTIFY = 'notify';
    const SCENARIO_DEV = 'dev';

    /* ticket state constants */
    const STATE_OPEN = 0;
    const STATE_RUNNING = 1;
    const STATE_CLOSED = 2;
    const STATE_SUBMITTED = 3;
    const STATE_UNKNOWN = 4;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $instance = $this;
        $this->on(self::EVENT_BEFORE_UPDATE, function($instance){
            $this->presaveAttributes = $this->getOldAttributes();
        });
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'updateEvent']);
        $this->on(self::EVENT_AFTER_DELETE, [$this, 'deleteEvent']);

        /* generate the token if it's a new record */
        $this->token = $this->isNewRecord ? bin2hex(openssl_random_pseudo_bytes(\Yii::$app->params['tokenLength']/2)) : $this->token;

        $this->backup_interval = $this->isNewRecord ? 300 : $this->backup_interval;
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ticket';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['exam_id', 'token', 'backup_interval'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['token', 'test_taker'], 'required', 'on' => self::SCENARIO_SUBMIT],
            [['start', 'ip'], 'required', 'on' => self::SCENARIO_DOWNLOAD],
            [['end'], 'required', 'on' => self::SCENARIO_FINISH],
            [['client_state'], 'required', 'on' => self::SCENARIO_NOTIFY],
            [['exam_id'], 'integer'],
            [['backup_interval'], 'integer', 'min' => 0],
            [['time_limit'], 'integer', 'min' => 0],
            [['exam_id'], 'validateExam', 'skipOnEmpty' => false, 'skipOnError' => false, 'on' => self::SCENARIO_DEFAULT],
            [['start', 'end', 'test_taker', 'ip', 'state', 'download_lock'], 'safe'],
            [['start', 'end', 'test_taker', 'ip', 'state', 'download_lock', 'backup_lock', 'restore_lock', 'bootup_lock'], 'safe', 'on' => self::SCENARIO_DEV],
            [['token'], 'unique'],
            [['token'], 'string', 'max' => 32],
            [['token'], 'checkIfClosed', 'on' => self::SCENARIO_SUBMIT],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'state' => 'State',
            'token' => 'Token',
            'exam.name' => 'Exam Name',
            'exam.subject' => 'Exam Subject',
            'exam_id' => 'Exam ID',
            'valid' => 'Valid',
            'validTime' => 'Valid for',
            'start' => 'Started',
            'end' => 'Finished',
            'duration' => 'Duration',
            'time_limit' => 'Time Limit',
            'download_progress' => 'Exam Download Progress',
            'client_state' => 'Client State',
            'ip' => 'IP Address',
            'test_taker' => 'Test Taker',
            'backup' => 'Backup',
            'backup_last' => 'Last Backup',
            'backup_last_try' => 'Last Backup Try',
            'backup_state' => 'Backup State',
            'backup_interval' => 'Backup Interval',
            'backup_size' => 'Overall Backup Size',
        ];
    }

    /**
     * Checks if attributes have changed
     * 
     * @param array $attributes - a list of attributes to check
     * @return bool
     */
    public function attributesChanged($attributes)
    {
        foreach($attributes as $attribute){
            if($this->presaveAttributes[$attribute] != $this->attributes[$attribute]){
                return true;
            }
        }
        return false;
    }

    public function getOwn()
    {
        return $this->exam->user->id == \Yii::$app->user->id ? true : false;
    }

    public function getConcerns()
    {
        return [
            'users' => [
                $this->exam->user ? $this->exam->user->id : null, //owner
            ],
            'roles' => [
                'ticket/view/all', //concerns all users with the ticket/view/all permission
            ],
        ];
    }

    /**
     * When the ticket is updated, this function emits the events
     * 
     * @return void
     */
    public function updateEvent()
    {
        if($this->attributesChanged([ 'start', 'end', 'test_taker' ])){
            $eventItem = new EventItem([
                'event' => 'ticket/' . $this->id,
                'priority' => 0,
                'concerns' => $this->concerns,
                'data' => [
                    'action' => 'update',
                ],
            ]);
            $eventItem->generate();
        }
        if($this->attributesChanged([ 'download_progress' ])){
            $eventItem = new EventItem([
                'event' => 'ticket/' . $this->id,
                'priority' => round($this->download_progress*100) == 100 ? 2 : 0,
                'data' => [
                    'download_progress' => yii::$app->formatter->format($this->download_progress, 'percent')
                ],
            ]);
            $eventItem->generate();
        }

        if($this->attributesChanged([ 'download_lock' ])){
            $eventItem = new EventItem([
                'event' => 'ticket/' . $this->id,
                'priority' => 2,
                'data' => [
                    'download_lock' => $this->download_lock,
                ],
            ]);
            $eventItem->generate();
        }

        if($this->attributesChanged([ 'backup_state' ])){
            $eventItem = new EventItem([
                'event' => 'ticket/' . $this->id,
                'priority' => 2,
                'data' => [
                    'backup_state' => yii::$app->formatter->format($this->backup_state, 'ntext'),
                ],
            ]);
            $eventItem->generate();
        }

        if($this->attributesChanged([ 'restore_state' ])){
            $eventItem = new EventItem([
                'event' => 'ticket/' . $this->id,
                'priority' => 2,
                'data' => [
                    'restore_state' => yii::$app->formatter->format($this->restore_state, 'ntext'),
                ],
            ]);
            $eventItem->generate();
        }

        if($this->attributesChanged([ 'backup_lock' ])){
            $eventItem = new EventItem([
                'event' => 'ticket/' . $this->id,
                'priority' => 2,
                'data' => [
                    'backup_lock' => $this->backup_lock,
                ],
            ]);
            $eventItem->generate();
        }

        if($this->attributesChanged([ 'restore_lock' ])){
            $eventItem = new EventItem([
                'event' => 'ticket/' . $this->id,
                'priority' => 2,
                'data' => [
                    'restore_lock' => $this->restore_lock,
                ],
            ]);
            $eventItem->generate();
        }

        if($this->attributesChanged([ 'client_state' ])){
            $eventItem = new EventItem([
                'event' => 'ticket/' . $this->id,
                'priority' => 1,
                'data' => [
                    'client_state' => $this->client_state,
                ],
            ]);
            $eventItem->generate();

            $act = new Activity([
                'ticket_id' => $this->id,
                'description' => 'Client state changed: ' .
                $this->presaveAttributes['client_state'] . ' -> ' . $this->client_state,
            ]);
            $act->save();

        }
        return;
    }

    /**
     * When the ticket is deleted, this function emits the events
     * 
     * @return void
     */
    public function deleteEvent()
    {
        $eventItem = new EventItem([
            'event' => 'ticket/' . $this->id,
            'data' => [
                'type' => 'delete',
                'action' => 'notice',
                'message' => 'The ticket with token ' . $this->token . ' has been deleted in another session.',
            ],
        ]);
        $eventItem->generate();

        return;
    }

    /**
     * Getter for the start time
     * 
     * @return integer 
     */
    public function getStartTime()
    {
        return $this->start;
    }

    /**
     * Setter for the start time
     *
     * @param integer $value - the start time
     * @return void
     */
    public function setStartTime($value)
    {
        if($this->start != $value){
            $eventItem = new EventItem([
                'event' => 'ticket/' . $this->id,
                'data' => [
                    'type' => 'action',
                    'action' => 'reload',
                    'container' => '#ticket-grid',
                ],
            ]);
            $eventItem->generate();

            $this->start = $value;
        }
    }

    /**
     * Mapping of the different states and the color classes
     *
     * @return array
     */
    public function getClassMap()
    {
        return [
            0 => 'success',
            1 => 'info',
            2 => 'danger',
            3 => 'warning',
        ];
    }

    /**
     * Just returns validity of the ticket.
     *
     * @return bool
     */
    public function getValid(){
        if($this->state == self::STATE_OPEN || $this->state == self::STATE_RUNNING){
            return $this->validTime !== false ? true : false;
        }
        return false;
    }

    /**
     * Returns if there is a backup
     *
     * @return bool
     */
    public function getBackup(){
        $backupDir = \Yii::$app->params['backupDir'] . '/' . $this->token . '/' . 'rdiff-backup-data';
        return Yii::$app->file->set($backupDir)->exists;
    }

    /**
     * Returns all backups associated to the ticket
     *
     * @return Backup[]
     */
    public function getBackups()
    {
        return Backup::findAll($this->token);
    }

    /**
     * Calulates the time the ticket will be valid as DateInterval.
     *
     * @return DateInterval|bool - Dateinterval object,
     *                             false, if not valid,
     *                             true, if it cannot expire
     */
    public function getValidTime(){
        if($this->state == self::STATE_OPEN || $this->state == self::STATE_RUNNING){
            $a = new \DateTime($this->start);
            #$a->add(new \DateInterval('PT2H'));
            if (is_int($this->time_limit) && $this->time_limit == 0) {
                return true;
            } else if (is_int($this->time_limit) && $this->time_limit > 0) {
                $a->add(new \DateInterval('PT' . intval($this->time_limit) . 'M'));
            } else if (is_int($this->exam->time_limit) && $this->exam->time_limit == 0) {
                return true;
            } else if (is_int($this->exam->time_limit) && $this->exam->time_limit > 0) {
                $a->add(new \DateInterval('PT' . intval($this->exam->time_limit) . 'M'));            
            } else {
                return true;
            }
            $b = new \DateTime('now');
            return $b > $a ? false : $b->diff($a);
        } else {
            return false;
        }
    }

    /**
     * Returns the duration of the test
     *
     * @return DateInterval object
     */    
    public function getDuration(){

        $a = new \DateTime($this->start);
        $b = new \DateTime($this->end);

        return $a->diff($b);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExam()
    {
        return $this->hasOne(Exam::className(), ['id' => 'exam_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActivities()
    {
        return $this->hasMany(Activity::className(), ['ticket_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRestores()
    {
        return $this->hasMany(Restore::className(), ['ticket_id' => 'id']);
    }

    /* Getter for exam name */
    public function getExamName()
    {
        return $this->exam->name;
    }

    /* Getter for exam subject */
    public function getExamSubject()
    {
        return $this->exam->subject;
    }

    public function getUserId()
    {
        return $this->exam->user_id;
    }

    /**
     * Runs a command in the shell of the system.
     * 
     * @param string $cmd - the command to run
     * @param string $lc_all - the value of the LC_ALL environment variable
     * @param integer $timeout - the SSH connection timeout
     * @return array - the first element contains the output (stdout and stderr),
     *                 the second element contains the exit code of the command
     */
    public function runCommand($cmd, $lc_all = "C", $timeout = 30)
    {

        $tmp = sys_get_temp_dir() . '/cmd.' . generate_uuid();
        $cmd = "ssh -i " . \Yii::$app->basePath . "/.ssh/rsa "
             . "-o UserKnownHostsFile=/dev/null "
             . "-o StrictHostKeyChecking=no "
             . "-o ConnectTimeout=" . $timeout . " "
             . "root@" . $this->ip . " "
             . escapeshellarg("LC_ALL=" . $lc_all . " " .  $cmd . " 2>&1") . " >" . $tmp;

        $output = array();
        $lastLine = exec($cmd, $output, $retval);

        if (!file_exists($tmp)) {
            $output = implode(PHP_EOL, $output);
        } else {
            $output = file_get_contents($tmp);
            @unlink($tmp);
        }

        return [ $output, $retval ];
    }

    /**
     * Returns all backups associated to the ticket
     *
     * @param string $attribute - the attribute
     * @param array $params
     * @return void
     */
    public function validateExam($attribute, $params)
    {

        $exam = Exam::findOne(['id' => $this->$attribute]);

        if(Yii::$app->user->can('ticket/create/all') || $this->own == true){
            if (!$exam->fileConsistency){
                $this->addError($attribute, 'As long as the exam file is not valid, no tickets can be created for this exam.');
            }
        }else{
            $this->addError($attribute, 'You are not allowed to perform this action on this exam.');
        }

    }

    /**
     * Generates an error message when the ticket is in closed state
     *
     * @param string $attribute - the attribute
     * @param array $params
     * @return void
     */
    public function checkIfClosed($attribute, $params)
    {
        if ($this->state != self::STATE_CLOSED) {
            $this->addError($attribute, 'This ticket is not in closed state.');
        }
    }



    /**
     * @inheritdoc
     *
     * @return TicketQuery the active query used by this AR class.
     */
    public static function find()
    {
        //$query = parent::find();
        $query = new TicketQuery(get_called_class());

        $query->select(['`ticket`.*', new \yii\db\Expression('(case
            WHEN (start is not null and end is not null and test_taker > "") THEN
                3 # submitted
            WHEN (start is not null and end is not null) THEN
                2 # closed
            WHEN (start is not null and end is null) THEN
                1 # running
            WHEN (start is null and end is null) THEN
                0 # open
            ELSE
                4 # unknown
            END
            ) as state')]);

        return $query;
    }

}
