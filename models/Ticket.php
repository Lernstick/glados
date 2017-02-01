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
 * @property integer $state
 * @property integer $exam_id
 * @property string $start
 * @property string $end
 * @property integer $duration
 * @property string $test_taker
 * @property string $ip
 * @property integer $download_progress
 * @property boolean $download_lock
 * @property integer $client_state
 *
 * @property Exam $exam
 */
class Ticket extends \yii\db\ActiveRecord
{

    public $state;
    public $status;
    private $presaveAttributes;

    /* scenario constants */
    const SCENARIO_DEFAULT = 'default';
    const SCENARIO_SUBMIT = 'submit';
    const SCENARIO_DOWNLOAD = 'download';
    const SCENARIO_FINISH = 'finish';
    const SCENARIO_NOTIFY = 'notify';

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

        $this->token = $this->isNewRecord ? bin2hex(openssl_random_pseudo_bytes(8)) : $this->token;
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
            [['exam_id'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['token', 'test_taker'], 'required', 'on' => self::SCENARIO_SUBMIT],
            [['start', 'ip'], 'required', 'on' => self::SCENARIO_DOWNLOAD],
            [['end'], 'required', 'on' => self::SCENARIO_FINISH],
            [['client_state'], 'required', 'on' => self::SCENARIO_NOTIFY],
            [['exam_id'], 'integer'],
            [['exam_id'], 'validateExam', 'skipOnEmpty' => false, 'skipOnError' => false, 'on' => self::SCENARIO_DEFAULT],
            [['start', 'end', 'test_taker', 'ip', 'state'], 'safe'],
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
            'download_progress' => 'Exam Download Progress',
            'client_state' => 'Client State',
            'ip' => 'IP Address',
            'test_taker' => 'Test Taker',
            'backup' => 'Backup',
            'backup_last' => 'Last Backup',
            'backup_last_try' => 'Last Backup Try',
            'backup_state' => 'Backup State',
        ];
    }

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

    public function getStartTime()
    {
        return $this->start;
    }

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
     * Mapping of the different statuses and the color classes
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
     * @return bool
     */
    public function getValid(){
        if($this->state == self::STATE_OPEN || $this->state == self::STATE_RUNNING){
            return $this->validTime ? true : false;
        }
        return false;
    }

    public function getBackup(){
        $backupDir = \Yii::$app->params['backupDir'] . '/' . $this->token . '/' . 'rdiff-backup-data';
        return Yii::$app->file->set($backupDir)->exists;
    }

    public function getBackups()
    {
        return Backup::findAll($this->token);
    }

    /**
     * Calulates the time the ticket will be valid as DateInterval.
     * @return DateInterval object of false if not valid
     */
    public function getValidTime(){
        $a = new \DateTime($this->start);
        $a->add(new \DateInterval('PT2H'));
        $b = new \DateTime('now');

        if($this->state == self::STATE_OPEN || $this->state == self::STATE_RUNNING){
            return $b > $a ? false : $b->diff($a);
        }
        return false;
    }

    /** Returns the duration of the test
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

    /*
    public function continueBootup()
    {
        $cmd = "ssh -i " . \Yii::$app->basePath . "/.ssh/rsa "
             . "-o UserKnownHostsFile=/dev/null "
             . "-o StrictHostKeyChecking=no "
             . "root@" . $this->ip . " "
             . "'echo 0 > /run/initramfs/restore'";
        $retval = exec(sprintf("%s 2>&1; echo $?", $cmd));
        return $retval;
    }*/

    /**
     * Runs a command in the shell of the system.
     * 
     * @param string $cmd - the command to run
     * @param string $lc_all - the value of the LC_ALL environment variable
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

/*
    public function getExamList()
    {
        $exams = Exam::find()->asArray()->all();
        return ArrayHelper::map($exams, 'id', function($exams){
                return $exams['subject'] . ' - ' . $exams['name'];
            }
        );
    }

    public function getSubjectList()
    {
        $exams = Exam::find()->asArray()->all();
        return ArrayHelper::map($exams, 'subject', 'subject');
    }
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

    public function checkIfClosed($attribute, $params)
    {
        if ($this->state != self::STATE_CLOSED) {
            $this->addError($attribute, 'This ticket is not in closed state.');
        }
    }



    /**
     * @inheritdoc
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
