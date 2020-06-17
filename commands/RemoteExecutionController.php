<?php

namespace app\commands;

use yii;
use app\commands\DaemonController;
use app\models\RemoteExecution;
use app\models\DaemonInterface;

/**
 * Remote Execution Daemon
 * This daemon run through the queue in the database table "remote_execution"
 */
class RemoteExecutionController extends DaemonController implements DaemonInterface
{

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

        $cmd = "ssh -i " . \Yii::$app->params['dotSSH'] . "/rsa " . $options
             . "root@" . $remote_execution->host . " "
             . escapeshellarg($remote_execution->env . " " .  $remote_execution->cmd . " 2>&1") . " >" . $tmp;        

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
     */
    public function lockItem ($remote_execution)
    {
        return $this->lock($remote_execution->id . "_remote_execution");
    }

    /**
     * @inheritdoc
     */
    public function unlockItem ($remote_execution)
    {
        return $this->unlock($remote_execution->id . "_remote_execution") && $remote_execution->delete();
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
            ->orderBy(['requested_at' => SORT_ASC]);

        if (($remote_execution = $query->one()) !== null) {
            return $remote_execution;
        }

        return null;
    }
}
