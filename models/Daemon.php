<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "daemon".
 *
 * @property integer $id
 * @property integer $pid
 * @property string $description 
 * @property string $started_at 
 * @property boolean $running
 */
class Daemon extends \yii\db\ActiveRecord
{

    const SIGTERM = 15;
    const SIGKILL = 9;
    const SIGHUP = 1;
    const SIGUSR1 = 10;

    private $presaveAttributes;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'daemon';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pid', 'uuid', 'description'], 'required'],
            [['pid'], 'integer'],
            [['running'], 'boolean'],
            [['state'], 'string', 'max' => 60],
            [['started_at'], 'safe'], 
            [['description'], 'string', 'max' => 254], 
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => 'Process ID',
            'uuid' => 'Process UUID',
            'state' => 'State',
            'description' => 'Description',
            'started_at' => 'Started At',
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->uuid = generate_uuid();

        $instance = $this;
        $this->on(self::EVENT_BEFORE_UPDATE, function($instance){
            $this->presaveAttributes = $this->getOldAttributes();
        });
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'updateEvent']);

    }

    public function getRunning()
    {
        if(file_exists('/proc/1')){
            if(file_exists('/proc/' . $this->pid)){
                return true;
            }
        }
        return false;
    }

    public function startBackup($id = '', $background = true)
    {
        return $this->start('backup/run', [escapeshellarg($id)], $background);
    }

    public function startRestore($id, $file, $date = 'now', $background = true, $restorePath = null)
    {
        return $this->start('restore/run', [escapeshellarg($id), escapeshellarg($file), escapeshellarg($date), escapeshellarg($restorePath)], $background);
    }

    public function start($command, $arguments = [], $background = true)
    {

        //TODO: validating $command!
        $cmd = \Yii::getAlias('@app') . '/' . 'yii ' . $command . ' ' . implode(' ', $arguments);
        //file_put_contents('/tmp/command', $cmd . PHP_EOL, FILE_APPEND);
        if ($background === true) {
            $this->pid = exec(sprintf("%s > /dev/null 2>&1 & echo $!", $cmd));
            //$this->save();
        } else {
            $this->pid = exec(sprintf("%s 2>&1; echo $?", $cmd));
        }
        return $this->pid;

    }

    public function stop()
    {
        posix_kill($this->pid, self::SIGTERM);
    }

    public function kill()
    {
        posix_kill($this->pid, self::SIGKILL);
    }

    public function reload()
    {
        posix_kill($this->pid, self::SIGHUP);
    }

    public function attributesChanged($attributes)
    {
        foreach ($attributes as $attribute) {
            if ($this->presaveAttributes[$attribute] != $this->attributes[$attribute]) {
                return true;
            }
        }
        return false;
    }

    public function updateEvent()
    {
        if($this->attributesChanged([ 'state' ])){
            $eventItem = new EventItem([
                'event' => 'daemon/' . $this->pid,
                'priority' => 0,
                'concerns' => ['users' => ['ALL']],
                'data' => [
                    'state' => yii::$app->formatter->format($this->state, 'text')
                ],
            ]);
            $eventItem->generate();
        }

    }


/*
    public function lock()
    {
        $pid_file = '/tmp/yii/daemon/' . $this->pid . '.pid';
        $pid_file = '/tmp/locktest';
        if(!file_exists(dirname($pid_file))){
            mkdir(dirname($pid_file), 0740, true);
        }

$this->transaction = Yii::$app->db->beginTransaction();
Yii::$app->db->createCommand("INSERT INTO `exam`.`activity` (`id`, `date`, `description`, `ticket_id`) VALUES (NULL, CURRENT_TIMESTAMP, 'start', '1003');")->execute();

        return;

        $this->mutex = \Mutex::create();
        if(\Mutex::trylock($this->mutex)){
            return true;
        }else{
            return false;
        }

//        $semaphore = sem_get($this->pid, 1, 0666, 1);
        $semaphore = sem_get(255, 1, 0600, 1);
        if(sem_acquire($semaphore, true)){
            return true;
        }else{
            return false;
        }

        $lock_file = fopen($pid_file, 'c');
        $got_lock = flock($lock_file, LOCK_EX | LOCK_NB, $wouldblock);
        if ($lock_file === false || (!$got_lock && !$wouldblock)) {
            return false;
        }
        else if (!$got_lock && $wouldblock) {
            return false;
        }

        // Lock acquired; let's write our UUID to the lock file.
        ftruncate($lock_file, 0);
        fwrite($lock_file, $this->uuid);
        return true;

    }

    public function unlock()
    {
        Yii::$app->db->createCommand("INSERT INTO `exam`.`activity` (`id`, `date`, `description`, `ticket_id`) VALUES (NULL, CURRENT_TIMESTAMP, 'stop', '1003');")->execute();
        $this->transaction->commit();
        \Mutex::unlock($this->mutex, true);
    }
*/

}

