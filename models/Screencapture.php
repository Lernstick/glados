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
        return FileHelper::normalizePath($this->screencaptureDir . '/' . $file);
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
        if ($this->ticket->state == Ticket::STATE_RUNNING) {
            // simulate a live stream
            $contents = str_replace("#EXT-X-PLAYLIST-TYPE:VOD", "#EXT-X-PLAYLIST-TYPE:EVENT", $contents);
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
        $stream_start = $this->starttime;
        $chunk_start = intval($matches[1]);
        $chunk_end = $this->getNexttime($chunk_start);
        $chunk_size = $chunk_end - $chunk_start;

        $ms_start = ($chunk_start - $stream_start)*1000;
        $ms_end = ($chunk_end - $stream_start)*1000;
        //var_dump($stream_start, $chunk_start, $chunk_end, $chunk_size);die();

        // write the header of the webvtt file
        $vtt = 'WEBVTT'. PHP_EOL . 'X-TIMESTAMP-MAP=MPEGTS:900000,LOCAL:00:00:00.000' . PHP_EOL . PHP_EOL;

        $keyloggerFiles = glob($this->screencaptureDir . '/keylogger*.key');
        $i=1;
        $vtt .= $i . PHP_EOL . $this->format_time($ms_start) . ' --> ' . $this->format_time($ms_end) . PHP_EOL;
        foreach($keyloggerFiles as $file) {
            preg_match('/keylogger([0-9]+)\.key/', $file, $matches);
            if (array_key_exists(1, $matches)) {
                $timestamp = intval($matches[1]);
                $next_chunk_end = $this->getNexttime($chunk_end);
                if ($timestamp >= $chunk_start && $timestamp + 10 <= $next_chunk_end) {
                    $lines = file($file, FILE_IGNORE_NEW_LINES);
                    foreach ($lines as $nl => $line) {
                        list($time, $msg) = explode(' ', $line, 2);
                        if (array_key_exists($nl+1, $lines)) {
                            list($ntime, $nmsg) = explode(' ', $lines[$nl+1], 2);
                        } else {
                            $ntime = $time + 1000;
                        }
                        $vtt .= '<' . $this->format_time($time - $stream_start*1000) . '>' . $this->format_subtitle($msg);
                        #$vtt .= $this->format_subtitle($msg);
                        $i++;
                    }
                }
            }
        }
        $vtt .= PHP_EOL . PHP_EOL;
        return $vtt;

        $s = $this->format_time($ms_start+123);
        $e = $this->format_time(($ms_start + $chunk_size*1000) - 434);

        return "WEBVTT
X-TIMESTAMP-MAP=MPEGTS:900000,LOCAL:00:00:00.000

$s --> $e
English subtitle 1 -Unforced- ($s to $e)


";
    }


    /**
     * Getter for the timestamp of the first video chunk file
     *
     * @return integer
     */
    public function getStarttime()
    {
        $dir = new \DirectoryIterator($this->screencaptureDir);
        $reftime = 9999999999;
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                preg_match('/video([0-9]+)\.ts/', $fileinfo->getFilename(), $matches);
                if (array_key_exists(1, $matches)) {
                    $reftime = $reftime > intval($matches[1]) ? intval($matches[1]) : $reftime;
                }
            }
        }
        return $reftime == 9999999999 ? 0 : $reftime;
    }

    /**
     * Gets the timestamp of the next video chunk given a timestamp
     *
     * @return integer
     */
    public function getNexttime($start)
    {
        $dir = new \DirectoryIterator($this->screencaptureDir);
        $reftime = 9999999999;
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                preg_match('/video([0-9]+)\.ts/', $fileinfo->getFilename(), $matches);
                if (array_key_exists(1, $matches)) {
                    $timestamp = intval($matches[1]);
                    if ($timestamp > $start) {
                        $reftime = $reftime > $timestamp ? $timestamp : $reftime;
                    }
                }
            }
        }
        return $reftime == 9999999999 ? 0 : $reftime;
    }


    /**
     * Converts milliseconds into time indicators in a webvtt file
     * Example: 1715123 will be converted into "00:28:35.123".
     *
     * @param int $ms milliseconds to convert
     * @return string
     */
    public function format_time($ms)
    {

        //$seconds = floor($ms/1000);
        $hours = floor($ms/3600000);
        $ms -= $hours*3600000;
        $mins = floor($ms/60000);
        $ms -= $mins*60000;
        $seconds = floor($ms/1000);
        $ms -= $seconds*1000;
        return sprintf('%02d:%02d:%02d.%03d', $hours, $mins, $seconds, $ms);
    }

    /**
     * Format a subtitle with correct escape sequences
     * Example: <ctrl> will be formatted into "&lt;ctrl&gt;".
     *
     * @param string $subtitle string to format
     * @return string
     */
    public function format_subtitle($subtitle)
    {
        $chars = [
            '&' => '&amp;',
            '<' => '&lt;',
            '>' => '&gt;',
        ];
        if ($subtitle == '') {
            $subtitle = '␣';
        }
        return str_replace(array_keys($chars), array_values($chars), $subtitle);
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
