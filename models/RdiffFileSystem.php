<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use app\models\Daemon;

class RdiffFileSystem extends Model
{

    public $root = '/';
    public $restoreUser = 'root';
    public $restoreHost = 'localhost';
//    private static $instance;
    private $_location = '.';
    private $_versions = [];
    private $_pwd = '.'; //evtl: this->_file
    public $dateRegex = '/[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}\+[0-9]{2}\:[0-9]{2}/';
    public $propertiesPopulated = false;
    private $_date = 'now';
    public $ticket;

    public function init()
    {
        //self::$instance = $this;
        parent::init();
    }

    public function getLocation()
    {
        return $this->_location;
    }

    public function setLocation($value)
    {
        if (($this->_location = realpath($value)) === false) {
            throw new NotFoundHttpException('No such file or directory.');
        }
    }

    public function getPath()
    {
        return FileHelper::normalizePath('/' . $this->_pwd);
    }

    public function getRemotePath()
    {
        return FileHelper::normalizePath($this->root . '/' . $this->_pwd);
    }

    public function getLocalPath()
    {
        return FileHelper::normalizePath($this->location . '/' . $this->_pwd);
    }

    public function slash($path = '/')
    {
        $this->_pwd = $path;
        $this->propertiesPopulated = false;

        if (count($this->versions) != 0) {
            return $this;
        }else{
            return null;
            //throw new NotFoundHttpException($this->path . ': No such file or directory.');
        }

    }



    private function populateProperties($force = false)
    {
        if ($this->propertiesPopulated !== true || $force === true) {
            $cmd = "rdiff-backup -l " . $this->localPath . " 2>&1";
            //var_dump($cmd . ' -> ' . $this->_date);
            $versions = [];
            $output = array();
            $lastLine = exec($cmd, $output, $retval);
            foreach ($output as $line) {
                if (preg_match($this->dateRegex, $line, $matches) === 1) {
                    $versions[] = $matches[0];
                }
            }
            $this->_versions = $versions;

            $this->propertiesPopulated = true;
        }

    }

    public function getVersions()
    {
        $this->populateProperties();
        return $this->_versions;
    }

    public function getVersion()
    {
        return $this->_date;
    }

    public function versionAt($date)
    {
        if (preg_match($this->dateRegex, $date, $matches) === 1) {
            $this->_date = $date;
            return $this;
        }else{
            throw new NotFoundHttpException($this->remotePath . ' (' . $date . ')' . ': No such file or directory.'); 
        }
    }

    public function getContents()
    {
        //TODO: if folder?
        return $this->restore(true);
    }

    public function restore($inline = false)
    {
        if ($inline === true) {
            $tmpFile = sys_get_temp_dir() . '/restore.' . generate_uuid() . '.' . basename($this->path);
            $cmd = "rdiff-backup --restore-as-of '" . $this->_date . "' " . $this->localPath . " " . $tmpFile . " 2>&1";
            //var_dump($cmd);
            $out = "";
            $output = array();
            $lastLine = exec($cmd, $output, $retval);
            if (file_exists($tmpFile)) {
                //restored
            }else{
                //not
            }
            foreach ($output as $line) {
                $out .= $line . PHP_EOL;
            }

            $contents = file_get_contents($tmpFile);
            @unlink($tmpFile);
            return $contents;
        }else{

            $daemon = new Daemon();
            $daemon->startRestore($this->ticket->id, $this->path, $this->_date);

        }
    }

    public function browse($path)
    {

        return true;

        $rootDir = \Yii::getAlias('@app/' . \Yii::$app->params['backupDir'] . '/' . $token);
        $absoltePath = $rootDir . '/' . $dir;  
        $files = [];

        if (substr($absoltePath, 0, strlen($rootDir)) === $rootDir) {
            $files[] = scandir($absoltePath);
        }
        return $files;


        //$backup->filesystem = new RdiffFileSystem(['location' => $rootDir]);

        $backup->filesystem->slash('Desktop/file.txt')->current;

        //restore file
        $backup->filesystem->slash('Desktop')->slash('file.txt')->versionAt('2016-06-01T12:41:44+02:00')->restore();
        //restore dir
        $backup->filesystem->slash('Desktop')->versionAt('2016-06-01T12:41:44+02:00')->restore();
        //restore all
        $backup->filesystem->slash()->versionAt('2016-06-01T12:41:44+02:00')->restore();        

        //Array of Objects of all increments of that file
        $backup->filesystem->slash('Desktop')->slash('file.txt')->increments;
        
        $backup->filesystem->slash(); //Obj of dir /home/user
        $backup->filesystem->slash('/'); //Obj of dir /home/user

        $backup->filesystem->slash('Desktop/file.txt.not.exist'); // null

        $backup->filesystem->slash('Desktop')->slash('file.txt')->path; // string "/Desktop/file.txt"
        $backup->filesystem->slash('Desktop')->slash('file.txt')->remotePath; // string "/home/user/Desktop/file.txt"
        $backup->filesystem->slash('Desktop')->slash('file.txt')->localPath; // string "/var/www/exam/basic/backups/[hash]/Desktop/file.txt"
        $backup->filesystem->slash('Desktop')->slash('file.txt')->dirname; // /home/user/Desktop
        $backup->filesystem->slash('Desktop')->slash('file.txt')->basename; // file.txt
        $backup->filesystem->slash('Desktop')->slash('file.txt')->extenstion; //txt

        $backup->filesystem->slash('Desktop')->type; // d:dir, f:file
        $backup->filesystem->slash('Desktop')->contents; //Array of Objects of all files and dirs in that dir
        $backup->filesystem->slash('Desktop/file.txt')->contents; //contents of the file
        $backup->filesystem->slash('Desktop/file.txt')->increments[1]->contents; //content of file (second latest version)
        $backup->filesystem->slash('Desktop/file.txt')->versionAt('2016-06-01T12:41:44+02:00')->contents;
        $backup->filesystem->slash('Desktop/file.txt')->restore(); // current version restore
        $backup->filesystem->slash('Desktop/file.txt')->current->restore(); // current version restore
        //$backup->filesystem->slash('Desktop/file.txt')->versions; //array of dates with the versions;

    }

}
