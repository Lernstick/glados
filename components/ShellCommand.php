<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use yii\base\Event;

class OutputEvent extends Event
{
    /**
     * @var string the line that the command produced
     */    
    public $line;

    /**
     * @var integer the channel in which the output came
     */    
    public $channel;
}

class ShellCommand extends Component
{

    /**
     * @event Event an event that is triggered when the command has started.
     */
    const COMMAND_STARTED = 'cmdStarted';

    /**
     * @event Event an event that is triggered when the command has stopped.
     */    
    const COMMAND_STOPPED = 'cmdStopped';

    /**
     * @event Event an event that is triggered when the command prints on STDOUT or STDERR.
     */
    const COMMAND_OUTPUT = 'cmdoutput';

    /**
     * @const integer the 3 input and output channels
     */
    const STDIN = 0;
    const STDOUT = 1;
    const STDERR = 2;

    /**
     * @inheritdoc
     */
    public function __construct($command) {
        $this->cmd = $command;
        parent::__construct();
    }

    /**
     * @var string the actual command to run
     */
    public $cmd;
    public $cwd;
    public $env;

    /**
     * @var array An array holding specification for the file descriptors
     * @see http://php.net/manual/de/function.proc-open.php
     */
    private $_descriptorspec;

    /**
     * @var array An array holding file descriptors
     * @see http://php.net/manual/de/function.proc-open.php
     */
    private $_pipes;

    /**
     * @var ressource The process descriptor returned by proc_open()
     * @see http://php.net/manual/de/function.proc-open.php
     */
    private $_pd;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        $this->_pipes = array();
        $this->_descriptorspec = array(
            self::STDIN => array('pipe', 'r'),
            self::STDOUT => array('pipe', 'w'),
            self::STDERR => array('pipe', 'w')
        );
    }

    public function run()
    {
        /* trigger the start event */
        $this->trigger(self::COMMAND_STARTED);

        $this->_pd = proc_open(
            $this->cmd,
            $this->_descriptorspec,
            $this->_pipes,
            $this->cwd,
            $this->env
        );

        if (is_resource($this->_pd)) {

            //stream_set_blocking($this->_pipes[0], 0);
            //stream_set_blocking($this->_pipes[1], 0);
            //stream_set_blocking($this->_pipes[2], 0);

            // setup descriptors to listen
            $write = null;
            $read = array(
                $this->_pipes[1],
                $this->_pipes[2]
            );
            $except = null;

            // loop until all pointers are at EOF
            while (!(empty($read) && empty($write) && empty($except)) && is_resource($this->_pd)){

                $r = $read;
                /**
                 * Wait for data. The @ is because stream_select() can fail, if another process sends
                 * SIGHUP. The function will then be interrupted, which ends in a false of stream_select().
                 */
                if ($n = @stream_select($r, $write, $except, 10)) {

                    foreach ($r as $pipe) {

                        // handle STDOUT
                        if($pipe === $this->_pipes[self::STDOUT]){
                            if (($line = fgets($pipe, 4096)) !== false) {
                                $event = new OutputEvent;
                                $event->line = $line;
                                $event->channel = self::STDOUT;

                                /* output event */
                                $this->trigger(self::COMMAND_OUTPUT, $event);
                            }
                            // if pointer is at EOF remove the pipe from observation
                            if (feof($pipe)) {
                                unset($read[0]);
                            }                            
                        }

                        // handle STDERR
                        if ($pipe === $this->_pipes[self::STDERR]) {
                            if (($line = fgets($pipe, 4096)) !== false) {
                                $event = new OutputEvent;
                                $event->line = $line;
                                $event->channel = self::STDERR;

                                /* output event */
                                $this->trigger(self::COMMAND_OUTPUT, $event);
                            }
                            // if pointer is at EOF remove the pipe from observation
                            if (feof($pipe)) {
                                unset($read[1]);
                            }                            
                        }
                    }
                }
            }

            /* close all open descriptors and store the return value of the command */
            fclose($this->_pipes[0]);
            fclose($this->_pipes[1]);
            fclose($this->_pipes[2]);
            $exit_status = proc_close($this->_pd);
        }

        /* trigger the stop event */
        $this->trigger(self::COMMAND_STOPPED);
        return $exit_status;
    }
}