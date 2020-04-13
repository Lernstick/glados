<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/**
 * This is the model class for Screencapture.
 *
 * @property string $todo
 *
 */
class Screencapture extends Model
{

    const MASTER = 'master.m3u8';

    /**
     * @var string The filesystem path to the master m3u8 file (the full absolute path)
     */
    public $master;
    public $ticket;

    public function getScreencaptureDir()
    {
        return \Yii::$app->params['scPath'] . '/' . $this->ticket->token;
    }

    /**
     * Return the Screencapture model related to the ticket id
     *
     * @param string $id id of the associated ticket
     * @return Screencapture|null
     */
    public function findOne($id)
    {
        if ( ($ticket = Ticket::findOne($id)) !== null) {
            $master = \Yii::$app->params['scPath'] . '/' . $ticket->token . '/' . self::MASTER;
            if (file_exists($master)) {
                return new Screencapture([
                    'master' => $master,
                    'ticket' => $ticket,
                ]);
            }
        }
        return null;
    }

    /**
     * Returns the segment file path
     *
     * @param string $file the token
     * @return string
     */
    public function segment($file)
    {
        $file = FileHelper::normalizePath($file);
        return FileHelper::normalizePath(StringHelper::dirname($this->master) . '/' . $file);
    }

    /**
     * Removes all screen captures
     *
     * @return void
     * @throws yii\base\ErrorException
     * @see https://www.yiiframework.com/doc/api/2.0/yii-helpers-basefilehelper#removeDirectory()-detail
     */
    public function delete()
    {
        return FileHelper::removeDirectory($this->screencaptureDir);
    }

    /**
     * Getter for the backup log
     *
     * @return array
     */
    public function getScreencaptureLog()
    {
        $log = [];
        if (file_exists($this->screencaptureDir . '/screen_capture.log')) {
            $lines = file($this->screencaptureDir . '/screen_capture.log');
            $log = array_merge($log, $lines);
        }
        return $log;
    }

}
