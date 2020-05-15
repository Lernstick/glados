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

    public $mimeTypes = [
        'm3u8' => 'application/x-mpegURL',
        'ts' => 'video/MP2T',
        'webvtt' => 'binary/octet-stream',
    ];

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
    public function streamFile($file)
    {
        $file = StringHelper::basename($file);
        return FileHelper::normalizePath(\Yii::$app->params['scPath'] . '/' . $this->ticket->token . '/' . $file);
    }

    /**
     * Return the contents of the requested file or null if the file does not exist.
     *
     * @return string|null
     */
    public function getFile($file)
    {
        if ($file == "subtitles.m3u8") {
            return $this->subtitles;
        }

        if ($file == "master.m3u8") {
            return $this->alteredMaster;
        }

        /* if $file ends with .webvtt */
        if (substr($file, -strlen(".webvtt")) === ".webvtt") {
            return $this->getSubtitleFile($file);
        }

        $path = $this->streamFile($file);
        if (file_exists($path)) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $contents = file_get_contents($path);
            if ($ext == 'm3u8') {
                $contents = $this->adjustPlaylist($contents);
            }
            return $contents;
        }
        return null;
    }

    /**
     * Adjust the contents of the playlist to simulate a live of vod stream, depending
     * on the ticket state.
     *
     * @return string
     */
    public function adjustPlaylist($contents)
    {
        if (false && $this->ticket->state == Ticket::STATE_RUNNING) {
            // simulate a live stream
            return str_replace("#EXT-X-ENDLIST", "", $contents);
        } else {
            // simulate a vod stream
            $contents = str_replace("#EXT-X-DISCONTINUITY", "", $contents);
            return str_replace("#EXT-X-PLAYLIST-TYPE:EVENT", "#EXT-X-PLAYLIST-TYPE:VOD", $contents) . "#EXT-X-ENDLIST" . PHP_EOL;
        }
    }

    /**
     * Returns the contents of the altered "master.m3u8" file with a subtitle track included
     *
     * @return string|null
     */
    public function getAlteredMaster()
    {
        $path = $this->streamFile('master.m3u8');
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES);
            
            foreach ($lines as $nl => $line) {
                if (preg_match('/^\#EXT\-X\-STREAM\-INF/', $line) !== 0) {
                    $lines[$nl] .= ',SUBTITLES="subs"';
                }
            }

            foreach ($lines as $nl => $line) {
                if ($line == "#EXT-X-VERSION:3") {
                    array_splice($lines, $nl+1, 0, '#EXT-X-MEDIA:TYPE=SUBTITLES,GROUP-ID="subs",NAME="English",DEFAULT=YES,AUTOSELECT=YES,FORCED=NO,LANGUAGE="en",URI="subtitles.m3u8"');
                }
            }
            $contents = implode(PHP_EOL, $lines);
            $contents = $this->adjustPlaylist($contents);
            return $contents;
        }
        return null;
    }

    /**
     * Returns the contents of the generated "subtitles.m3u8" file for the live stream, or null if
     * it cannot be generated. This file is generated from the "video.m3u8" by just replacing the
     * video[timestamp].ts file entries with subtitle[timestamp].webvtt
     *
     * @return string|null
     */
    public function getSubtitles()
    {
        if ( ($contents = $this->getFile('video.m3u8')) !== null ) {
            $contents = preg_replace('/video([0-9]+)\.ts/', 'subtitles\1.webvtt', $contents);
            return $contents;
        } else {
            return null;
        }
        
    }

    /**
     * Returns the contents of the generated "subtitle[timestamp].webvtt" file for the live stream.
     * This is generated using the keylogger[timestamp].key files in the same location as the screen
     * capture stream files.
     *
     * @return string
     */
    public function getSubtitleFile($file)
    {

        preg_match('/subtitles([0-9]+)\.webvtt/', $file, $matches);
        $timestamp = $matches[1];
        $start = 1589555303;
        $secs = intval($timestamp) - $start;

        $hours = floor($secs/3600);
        $secs -= $hours*3600;
        $mins = floor($secs/60);
        $secs -= $mins*60;
        $next = $secs + 8;

        $hours = sprintf('%02d', $hours);
        $mins = sprintf('%02d', $mins);
        $secs = sprintf('%02d', $secs);
        $next = sprintf('%02d', $next);

        return "WEBVTT
X-TIMESTAMP-MAP=MPEGTS:900000,LOCAL:00:00:00.000

$hours:$mins:$secs.000 --> $hours:$mins:$next.000
English subtitle 1 -Unforced- ($hours:$mins:$secs.000)


";
    }

    /**
     * Return the mimeType of a given file
     *
     * @return string
     */
    public function getMimeType($file)
    {
        $path = $this->streamFile($file);
        $ext = file_exists($path) ? pathinfo($path, PATHINFO_EXTENSION) : "mp4";
        return array_key_exists($ext, $this->mimeTypes) ? $this->mimeTypes[$ext] : 'application/octet-stream';
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
