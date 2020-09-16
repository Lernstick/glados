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

    /* Globs for the hls live stream and keylogger files */
    const GLOB_MASTER = 'master*.m3u8';
    const GLOB_PLAYLIST = 'video*.m3u8';
    const GLOB_SEGMENT = 'video*.ts';
    const GLOB_KEYLOGGER = 'keylogger*.key';

    /**
     * @var array The filesystem path to all the master m3u8 files (only basenames)
     */
    public $masters;

    /* hls headers and tags */
    const HLS_HEADER_SUBTITLES = '#EXT-X-MEDIA:' .
        'TYPE=SUBTITLES,' .
        'GROUP-ID="subs",' .
        'NAME="{name}",' .
        'DEFAULT=YES,' .
        'AUTOSELECT=YES,' .
        'FORCED=NO,' .
        'LANGUAGE="en",' .
        'CHARACTERISTICS="public.accessibility.transcribes-spoken-dialog, public.accessibility.describes-music-and-sound",' .
        'URI="subtitles{timestamp}.m3u8"';
    const HLS_TAG_VOD = '#EXT-X-PLAYLIST-TYPE:VOD';
    const HLS_TAG_LIVE = '#EXT-X-PLAYLIST-TYPE:EVENT';
    const HLS_TAG_ENDLIST = '#EXT-X-ENDLIST';
    const HLS_TAG_DISCONTINUITY = '#EXT-X-DISCONTINUITY';
    const HLS_TAG_VERSION = '#EXT-X-VERSION:3';
    const HLS_TAG_DURATION = '#EXTINF'; #Format is #EXTINF:<duration>,[<title>]

    public $ticket;

    public $mimeTypes = [
        'm3u8' =>   'application/x-mpegURL',
        'ts' =>     'video/MP2T',
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
            $masters = glob(\Yii::$app->params['scPath'] . '/' . $ticket->token . '/' . self::GLOB_MASTER);
            //rsort($masters);
            array_walk($masters, function(&$item){
                $item = basename($item);
            });

            if (!empty($masters)) {
                return new Screencapture([
                    'masters' => $masters,
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
        if (preg_match('/^subtitles([0-9]+)\.m3u8$/', $file, $matches) !== 0) {
            return $this->subtitles($matches[1]);
        }

        if (preg_match('/^master([0-9]+)\.m3u8$/', $file, $matches) !== 0) {
            return $this->master($matches[1]);
        }

        /* if $file ends with .webvtt */
        if (substr($file, -strlen(".webvtt")) === ".webvtt") {
            return $this->getSubtitleSegment($file);
        }

        if (preg_match('/^video([0-9]+)\.m3u8$/', $file, $matches) !== 0) {
            return $this->playlist($matches[1]);
        }

        $path = $this->streamFile($file);
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return null;
    }


    /**
     * Adjust the contents of the playlist to simulate a live or vod stream, depending
     * on the ticket state and the stream.
     *
     * @param string $timestamp the master file timestamp
     * @return string|null
     */
    public function playlist($timestamp)
    {
        $file = "video" . $timestamp . ".m3u8";
        $path = $this->streamFile($file);
        if (file_exists($path)) {
            $contents = file_get_contents($path);

            if ( array_search("master" . $timestamp . ".m3u8", $this->masters) === 0) {
                if ($this->ticket->state == Ticket::STATE_RUNNING) {
                    // simulate a live stream
                    $contents = str_replace(self::HLS_TAG_VOD, self::HLS_TAG_LIVE, $contents);
                    return str_replace(self::HLS_TAG_ENDLIST, "", $contents);
                }
            }
            // simulate a vod stream
            $contents = str_replace(self::HLS_TAG_DISCONTINUITY, "", $contents);
            $contents = str_replace(self::HLS_TAG_ENDLIST, "", $contents);
            return str_replace(self::HLS_TAG_LIVE, self::HLS_TAG_VOD, $contents) . self::HLS_TAG_ENDLIST . PHP_EOL;

        }
        return null;

    }

    /**
     * Adjust the contents of the playlist to simulate a live or vod stream, depending
     * on the ticket state and the stream.
     *
     * @param string $contents the contents of the file
     * @param string $file the requested file name
     * @return string
     */
    public function adjustPlaylist($contents, $file)
    {
        if ($this->ticket->state == Ticket::STATE_RUNNING) {
            // simulate a live stream
            $contents = str_replace(self::HLS_TAG_VOD, self::HLS_TAG_LIVE, $contents);
            return str_replace(self::HLS_TAG_ENDLIST, "", $contents);
        } else {
            // simulate a vod stream
            $contents = str_replace(self::HLS_TAG_DISCONTINUITY, "", $contents);
            return str_replace(self::HLS_TAG_LIVE, self::HLS_TAG_VOD, $contents) . self::HLS_TAG_ENDLIST . PHP_EOL;
        }
    }

    /**
     * Returns the contents of the altered "master.m3u8" file with a subtitle track included
     *
     * @param string $timestamp the master file timestamp
     * @return string|null
     */
    public function master($timestamp)
    {
        $file = "master" . $timestamp . ".m3u8";
        $path = $this->streamFile($file);
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES);
            
            foreach ($lines as $nl => $line) {
                if (preg_match('/^\#EXT\-X\-STREAM\-INF/', $line) !== 0) {
                    $lines[$nl] .= ',SUBTITLES="subs"';
                }
            }

            foreach ($lines as $nl => $line) {
                if ($line == self::HLS_TAG_VERSION) {
                    array_splice($lines, $nl+1, 0, substitute(self::HLS_HEADER_SUBTITLES, [
                        'name' => \Yii::t('app', 'keystrokes from Keylogger'),
                        'timestamp' => $timestamp,
                    ]));
                }
            }
            return implode(PHP_EOL, $lines);
        }
        return null;
    }

    /**
     * Returns the contents of the generated "subtitles[timestamp].m3u8" file for the live stream, or null if
     * it cannot be generated. This file is generated from the "video[timestamp].m3u8" by just replacing the
     * video[timestamp].ts file entries with subtitle[timestamp].webvtt
     *
     * @param string $timestamp the subtitles file timestamp
     * @return string|null
     */
    public function subtitles($timestamp)
    {
        if ( ($contents = $this->getFile('video' . $timestamp . '.m3u8')) !== null ) {
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
    public function getSubtitleSegment($file)
    {

        preg_match('/subtitles([0-9]+)\.webvtt/', $file, $matches);
        $stream_start = $this->starttime($matches[1]);
        $chunk_start = intval($matches[1]);
        $chunk_end = $this->getNexttime($chunk_start);
        $chunk_size = $chunk_end - $chunk_start;

        $ms_start = ($chunk_start - $stream_start)*1000;
        $ms_end = ($chunk_end - $stream_start)*1000;

        // write the header of the webvtt file
        $vtt = 'WEBVTT'. PHP_EOL . 'X-TIMESTAMP-MAP=MPEGTS:0,LOCAL:00:00:00.000' . PHP_EOL . PHP_EOL;

        $keyloggerFiles = glob($this->screencaptureDir . '/' . self::GLOB_KEYLOGGER);
        $i=1;
        $vtt .= $i . PHP_EOL . $this->format_time($ms_start) . ' --> ' . $this->format_time($ms_end) . PHP_EOL;
        foreach($keyloggerFiles as $file) {
            if ( preg_match('/keylogger([0-9]+)\.key/', $file, $matches) === 1) {
                $key_starttime = intval($matches[1]);
                $next_chunk_end = $this->getNexttime($chunk_end);
                if ($key_starttime + 10 >= $chunk_start && $key_starttime - 10 <= $chunk_end) {
                    $lines = file($file, FILE_IGNORE_NEW_LINES);
                    foreach ($lines as $nl => $line) {
                        list($time, $msg) = explode(' ', $line, 2);
                        $time = intval($time);
                        if ( $time >= $chunk_start*1000 && $time <= $chunk_end*1000 ) {
                            $vtt .= '<' . $this->format_time($time - $stream_start*1000) . '>' . $this->format_subtitle($msg);
                        }
                        $i++;
                    }
                }
            }
        }
        return $vtt . PHP_EOL . PHP_EOL;
    }


    /**
     * Getter for the timestamp of the first video chunk file
     *
     * @param integer $timestamp of the current subtitle file
     * @return integer timestamp
     */
    public function starttime($timestamp)
    {
        $playlists = glob($this->screencaptureDir . '/' . self::GLOB_PLAYLIST);
        foreach ($playlists as $key => $playlist) {
            $lines = file($playlist);
            foreach ($lines as $key => $line) {
                if (trim($line) == 'video' . $timestamp . '.ts') {
                    foreach ($lines as $key => $line) {
                        if (preg_match('/video([0-9]+)\.ts/', $line, $matches) === 1) {
                            return intval($matches[1]);
                        }
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Gets the timestamp of the next video chunk given the current chunk start timestamp
     *
     * @param integer $start current timestamp
     * @return integer timestamp
     */
    public function getNexttime($start)
    {
        return $this->getTime($start, +1);
    }

    /**
     * Gets the timestamp of the previous video chunk given the current chunk start timestamp
     *
     * @param integer $start current timestamp
     * @return integer timestamp
     */
    public function getPrevtime($start)
    {
        return $this->getTime($start, -1);
    }

    /**
     * Gets the timestamp of the video chunk [[delta]] steps away given the current chunk start
     * timestamp
     *
     * @param integer $start current timestamp
     * @param integer $delta number of steps to go
     * @return integer timestamp
     */
    public function getTime($start, $delta)
    {
        $segments = glob($this->screencaptureDir . '/' . self::GLOB_SEGMENT);
        foreach ($segments as $key => $segment) {
            if (basename($segment) == 'video' . $start . '.ts' && array_key_exists($key+$delta, $segments)) {
                if ( preg_match('/video([0-9]+)\.ts/', $segments[$key+$delta], $matches) === 1){
                    return intval($matches[1]);
                }
            }
        }
        return $delta > 0 ? 99999999999999 : 0;
    }


    /**
     * Calculate the lenth of a file given by its name
     *
     * @param string $file name of the file
     * @return integer length in seconds
     */
    public function length($file)
    {
        $length = 0.0;
        $deeper = true;
        $contents = $this->getFile($file);
        foreach (explode(PHP_EOL, $contents) as $line) {
            if (substr($line, 0, strlen(self::HLS_TAG_DURATION)) === self::HLS_TAG_DURATION) {
                if ( preg_match('/' . self::HLS_TAG_DURATION . ':([0-9\.]+)/', $line, $matches) === 1){
                    $length += floatval($matches[1]);
                }
                $deeper = false;
            } else if ($deeper === true && !empty($line) && substr($line, 0, 1) !== "#") {
                $length += $this->length($line);
            }
        }
        return $length;
    }

    /**
     * Getter for the lengths array. For each master-playlist item  in [[masters]] calculate
     * the length.
     *
     * @return array list of lengths in seconds
     */
    public function getLengths()
    {
        $lengths = [];
        foreach ($this->masters as $key => $master) {
            $lengths[] = $this->length($master);
        }
        return $lengths;
    }

    public function getScreencaptures()
    {
        return array_map(function($master, $length) {
            return [
                'master' => $master,
                'length' => $length,
                'url' => Url::to(['screencapture/view', 
                    'id' => $this->ticket->id,
                    'file' => basename($master),
                ]),
            ];
        }, $this->masters, $this->lengths);
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
            '␣' => '&nbsp;',
            PHP_EOL => '&nbsp;',
        ];

        if ($subtitle == '') {
            $subtitle = PHP_EOL;
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

    /**
     * Getter for the keylogger log
     *
     * @return array
     */
    public function getKeyloggerLog()
    {
        $keyloggerFiles = glob($this->screencaptureDir . '/' . self::GLOB_KEYLOGGER);
        $i=1;
        $log = '';
        foreach($keyloggerFiles as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            foreach ($lines as $nl => $line) {
                list($time, $msg) = explode(' ', $line, 2);
                $log .= $msg;
            }
        }
        return $log;
    }
}
