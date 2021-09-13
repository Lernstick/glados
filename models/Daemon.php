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
class Daemon extends LiveActiveRecord
{

    const SIGTERM = 15;
    const SIGKILL = 9;
    const SIGHUP = 1;
    const SIGUSR1 = 10;

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
            [['state'], 'string', 'max' => 254],
            [['started_at', 'load'], 'safe'], 
            [['description'], 'string', 'max' => 254], 
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('daemons', 'ID'),
            'pid' => \Yii::t('daemons', 'PID'),
            'uuid' => \Yii::t('daemons', 'Process UUID'),
            'state' => \Yii::t('daemons', 'State'),
            'load' => \Yii::t('daemons', 'Load'),
            'description' => \Yii::t('daemons', 'Description'),
            'started_at' => \Yii::t('daemons', 'Started At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'pid' => \Yii::t('daemons', 'Process ID'),
            'state' => \Yii::t('daemons', 'The last reported state'),
            'load' => \Yii::t('daemons', 'The load of the last 5 minutes'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->uuid = generate_uuid();
    }

    /**
     * @inheritdoc
     */
    public function getLiveFields()
    {
        return [
            'state' => ['priority' => 0],
            'load' => [
                'priority' => 0,
                'data' => function ($field, $model) {
                    return ['load' => yii::$app->formatter->format($model->{$field}, 'percent')];
                },
            ],
            'memory' => [
                'priority' => 0,
                'data' => function ($field, $model) {
                    return ['memory' => yii::$app->formatter->format($model->{$field}, ['shortSize', 'decimals' => 1])];
                },
            ],
        ];
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

    public function startDaemon()
    {
        return $this->start('daemon/run', [], true);
    }

    public function startBackup($id = '', $background = true)
    {
        return $this->start('backup/run', [escapeshellarg($id)], $background);
    }

    public function startNotify($id = '', $background = true)
    {
        return $this->start('notify/run', [escapeshellarg($id)], $background);
    }

    public function startDownload($id = '', $background = true)
    {
        return $this->start('download/run', [escapeshellarg($id)], $background);
    }

    public function startAnalyzer($id = '', $background = true)
    {
        return $this->start('analyze/run', [escapeshellarg($id)], $background);
    }

    public function startRestore($id, $file, $date = 'now', $background = true, $restorePath = null)
    {
        $args = [
            escapeshellarg($id),
            escapeshellarg($file),
            escapeshellarg($date),
        ];
        $restorePath == null ?: $args[] = escapeshellarg($restorePath);
        return $this->start('restore/run', $args, $background);
    }

    public function startFetch($id, $background = true)
    {
        return $this->start('fetch/run', [escapeshellarg($id)], $background);
    }

    public function start($command, $arguments = [], $background = true)
    {

        //TODO: validating $command!
        $cmd = \Yii::getAlias('@app') . '/' . 'yii ' . $command . ' ' . implode(' ', $arguments);
        if ($background === true) {
            $this->pid = exec(sprintf("%s > /dev/null 2>&1 & echo $!", $cmd));
            //$this->save();
        } else {
            $this->pid = exec(sprintf("%s 2>&1; echo $?", $cmd));
        }
        return $this->pid;

    }

    /**
     * Method to send SIGTERM (15) to the process corresponding to the model.
     * @return void
     */
    public function stop()
    {
        posix_kill($this->pid, self::SIGTERM);
        $this->state = substitute('{signal} sent at {time}.', [
            'signal' => 'SIGTERM',
            'time' => yii::$app->formatter->format(time(), 'time'),
        ]);
        $this->save(false);
    }

    /**
     * Method to send SIGKILL (9) to the process corresponding to the model.
     * @return void
     */
    public function kill()
    {
        posix_kill($this->pid, self::SIGKILL);
        $this->state = substitute('{signal} sent at {time}.', [
            'signal' => 'SIGKILL',
            'time' => yii::$app->formatter->format(time(), 'time'),
        ]);
        $this->save(false);
    }

    /**
     * Method to send SIGHUP (1) to the process corresponding to the model.
     * @return void
     */
    public function hup()
    {
        posix_kill($this->pid, self::SIGHUP);
        $this->state = substitute('{signal} sent at {time}.', [
            'signal' => 'SIGHUP',
            'time' => yii::$app->formatter->format(time(), 'time'),
        ]);
        $this->save(false);
    }
}