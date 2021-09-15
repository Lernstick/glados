<?php

namespace app\commands;

use yii;
use yii\db\Expression;
use app\commands\NetworkController;
use app\models\RemoteExecution;
use app\models\DaemonInterface;

/**
 * Remote Executer
 *
 * This daemon run through the queue in the database table "remote_execution"
 */
class RemoteExecutionController extends NetworkController implements DaemonInterface
{

    /**
     * @inheritdoc
     */
    public $lock_type = 'remote_execution';

    /**
     * @inheritdoc
     */
    public $lock_property = 'remote_execution_lock';

    /**
     * @var RemoteExecution The element in processing at the moment 
     */    
    public $remote_execution = null;

    /**
     * @var array SSH options
     */    
    public $sshOptions = [
        'UserKnownHostsFile'    => '/dev/null',
        'StrictHostKeyChecking' => 'no',
        'BatchMode'             => 'yes',
        'ConnectTimeout'        => 30,
    ];

    /**
     * @inheritdoc
     */
    public function doJobOnce ($id = '')
    {
        if (($this->remote_execution = $this->getNextItem()) !== null && $this->lockItem($this->remote_execution)) {
            $this->processItem($this->remote_execution);
            $this->unlockItem($this->remote_execution);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function doJob ($id = '')
    {
        $this->calcLoad(0);
        while (true) {
            $this->remote_execution = null;

            if ($id != ''){
                $this->remote_execution =  RemoteExecution::findOne(['id' => $id]);
            } else {
                $this->remote_execution = $this->getNextItem();
            }

            if($this->remote_execution  !== null && $this->lockItem($this->remote_execution)) {
                $this->processItem($this->remote_execution);
                $this->unlockItem($this->remote_execution);
                $this->calcLoad(1);
            }

            if ($id != '') {
                return;
            }

            pcntl_signal_dispatch();
            sleep(rand(5, 10));
            $this->calcLoad(0);
        }
    }

    /**
     * @inheritdoc
     * @return array the first element contains the output (stdout and stderr), 
     * the second element contains the exit code of the command
     */
    public function processItem ($remote_execution)
    {
        $this->remote_execution = $remote_execution;
        $tmp = sys_get_temp_dir() . '/cmd.' . generate_uuid();

        $options = "";
        foreach ($this->sshOptions as $key => $value) {
            $options .= " -o " . $key . "=" . $value . " ";
        }

        $cmd = substitute('ssh -i {identity} {options} {user}@{ip} {cmd} >{file}', [
            'identity' => escapeshellarg(\Yii::$app->params['dotSSH'] . '/rsa'),
            'options' => $options,
            'user' => 'root',
            'ip' => $remote_execution->host,
            'cmd' => escapeshellarg($remote_execution->env . " " .  $remote_execution->cmd . " 2>&1"),
            'file' => escapeshellarg($tmp),
        ]);

        $output = array();

        $this->logInfo('Executing command: ' . $cmd, true, false, true);
        $lastLine = exec($cmd, $output, $retval);

        if (!file_exists($tmp)) {
            $output = implode(PHP_EOL, $output);
        } else {
            $output = file_get_contents($tmp);
            @unlink($tmp);
        }

        $this->logInfo('retval: ' . $retval, false, false, true);
        $this->logInfo('output: ' . $output, false, false, true);

        return [ $output, $retval ];
    }

    /**
     * @inheritdoc
     *
     * Determines the next command to execute remotely
     *
     * @return RemoteExecution|null
     */
    public function getNextItem ()
    {
        $this->pingOthers();

        $query = RemoteExecution::find()
            // only if it was requested <=10 minutes ago, else the item is left abandoned.
            ->where([
                '>',
                'requested_at',
                new Expression('NOW() - INTERVAL 10 MINUTE')
            ])
            ->orderBy(['requested_at' => SORT_ASC]);

        if (($remote_execution = $query->one()) !== null) {
            return $remote_execution;
        }

        return null;
    }
}