<?php

namespace app\components;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
* @inheritdoc
*/
class customResponse extends \yii\web\Response
{

    /**
     * @event ResponseEvent an event that is triggered at every chunk of 8MB while [[send()]].
     */
    const EVENT_WHILE_SEND = 'whileSend';

    /**
     * @var integer progress of a downloading stream in bytes.
     */
    public $progress;

    /**
     * @var float Amount of time (in seconds) sent since the last EVENT_WHILE_SEND
     */
    public $time;

    /**
     * @var integer Amount of data (in Bytes) sent since the last EVENT_WHILE_SEND
     */
    public $data;

    /**
     * @var integer bandwidth limitation, value 0 for no limit.
     */
    public $bandwidth = 0;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function sendContent()
    {
        if ($this->stream === null) {
            echo $this->content;
            return;
        }
        set_time_limit(0); // Reset time limit for big files
        $this->time =  microtime(true);

#        $chunkSize = 1 * 1024 * 1024; // 1MB per chunk, should be a factor of $triggerSize
        $triggerSize = 8 * 1024 * 1024; //trigger EVENT_WHILE_SEND every 8MB

        $chunkSize = $this->bandwidth != 0 ? floor($this->bandwidth / 1000) : $triggerSize;

        $read = 0;
        if (is_array($this->stream)) {
            list ($handle, $begin, $end) = $this->stream;
            fseek($handle, $begin);
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                echo fread($handle, $chunkSize);
                flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
                usleep(1000);
                $read += $chunkSize;
                if($read >= $triggerSize){
                    $this->data = $read;
                    $read = 0;
                    $this->progress = $pos;
                    $now = microtime(true);
                    $this->time =  $now - $this->time;
                    $this->trigger(self::EVENT_WHILE_SEND);
                    $this->time = $now;
                }
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                echo fread($this->stream, $chunkSize);
                flush();
                usleep(1000);
                $read += $chunkSize;
                if($read >= $triggerSize){
                    $this->data = $read;
                    $read = 0;
                    $this->progress = $pos;
                    $now = microtime(true);
                    $this->time =  $now - $this->time;
                    $this->trigger(self::EVENT_WHILE_SEND);
                    $this->time = $now;
                }
            }
            fclose($this->stream);
        }
    }


}

