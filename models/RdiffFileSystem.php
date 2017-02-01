<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
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
    public $excludeDirs = ['.', '..', 'rdiff-backup-data'];
    //public $ticket;

    public function init()
    {
        parent::init();
    }

    public function getLocation()
    {
        return $this->_location;
    }

    public function setLocation($value)
    {
        if (($this->_location = realpath($value)) === false) {
            //throw new NotFoundHttpException('No such file or directory.');
        }
    }

    public function getPath()
    {
        return FileHelper::normalizePath('/' . $this->_pwd);
    }

    public function getBasename()
    {
        return StringHelper::basename(FileHelper::normalizePath('/' . $this->_pwd));
    }

    public function getRemotePath()
    {
        return FileHelper::normalizePath($this->root . '/' . $this->_pwd);
    }

    public function getLocalPath()
    {
        return FileHelper::normalizePath($this->location . '/' . $this->_pwd);
    }

    public function getIncrementsPath()
    {
        return FileHelper::normalizePath($this->location . '/rdiff-backup-data/increments/' . dirname($this->_pwd));
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

            $versions = [];

            if ($this->path == "") {
                $list = array_diff(scandir($this->location . '/rdiff-backup-data'), $this->excludeDirs);
                foreach ($list as $item) {
                    if (@strpos($item, 'increments') === 0) {
                        if (preg_match($this->dateRegex, $item, $matches) === 1) {
                            $versions[] = $matches[0];
                        }
                    }
                }
            } else {
                $list = array_diff(scandir($this->incrementsPath), $this->excludeDirs);
                foreach ($list as $item) {
                    if (@strpos($item, $this->basename) === 0) {
                        if (preg_match($this->dateRegex, $item, $matches) === 1) {
                            $versions[] = $matches[0];
                        }
                    }
                }
            }

            if (/*count($versions) == 0 && */file_exists($this->localPath)) {
                $versions[] = 'now';
            }

            $this->_versions = array_unique($versions);
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
        if ($date == 'now' || $date == 'all' || preg_match($this->dateRegex, $date, $matches) === 1) {
            $this->_date = $date;
            return $this;
        }else{
            return null;
            //throw new NotFoundHttpException($this->remotePath . ' (' . $date . ')' . ': No such file or directory.'); 
        }
    }

    public function getContents()
    {

        if ($this->type == 'file') {
            //return $this->restore(true);
            $contents = [];
            foreach ($this->versions as $version) {
                $a = new RdiffFileSystem([
                    'root' => $this->root,
                    'location' => $this->location,
                    'restoreUser' => $this->restoreUser,
                    'restoreHost' => $this->restoreHost,
                ]);
                $a->slash($this->path)->versionAt($version);
                $contents[] = $a;
            }
            return $contents;
        } else if ($this->type == 'dir') {

            $listLocal = [];
            if ($this->_date == 'now' || $this->_date == 'all') {
                // find current files
                $listLocal = array_diff(scandir($this->localPath), $this->excludeDirs);
            }

            $listInc = [];
            if ($this->_date != 'now') {
                // find files in the inrements dir
                if (file_exists($this->incrementsPath . '/' . $this->basename)){
                    $list = array_diff(scandir($this->incrementsPath . '/' . $this->basename), $this->excludeDirs);
                } else {
                    $list = [];
                }

                foreach ($list as $key => $item) {
                    if ($this->_date == 'all') {
                        if (preg_match($this->dateRegex, $item, $matches) === 1) {
                            $listInc[] = current(explode("." . $matches[0], $item));
                        }
                    } else {
                        if (strpos($item, $this->_date) !== false) {
                        //if (preg_match($this->_date, $item, $matches) === 1) {
                            $listInc[] = current(explode("." . $this->_date, $item));
                        }
                    }
                }
            }
           
            $list = array_unique(array_merge($listLocal, $listInc));

            $contents = [];
            foreach ($list as $item) {
                $fs = new RdiffFileSystem([
                    'root' => $this->root,
                    'location' => $this->location,
                    'restoreUser' => $this->restoreUser,
                    'restoreHost' => $this->restoreHost,
                ]);
                $fs->slash($this->path . '/' . $item)->versionAt($this->_date);
                $contents[] = $fs;
            }
            return $contents;
        }
    }

    public function getType()
    {
        if (file_exists($this->localPath)) {
            return filetype($this->localPath);
        } else {
            return 'file'; //TODO get filetype via increments
        }
    }

    public function getState()
    {
        /* reverse state if it's a whiteout file */
        if (strpos($this->basename, '.wh.') === 0) {
            $state = $this->getRealState();
            return $state == 'missing' ? 'normal' : 'missing';
        } else {
            return $this->getRealState();
        }
    }

    public function getDisplayName()
    {
        if (strpos($this->basename, '.wh.') === 0) {
            return substr($this->basename, 4);
        } else {
            return $this->basename;
        }
    }

    private function getRealState()
    {
        if ($this->version == 'now' && file_exists($this->localPath)) {
            return 'normal';
        } else {
            foreach (array_diff(scandir($this->incrementsPath), $this->excludeDirs) as $item) {
                if (strpos($item, $this->basename) === 0) {
                    if (preg_match($this->dateRegex, $item, $matches) === 1) {
                        if ($matches[0] == $this->_date) {
                            if (substr_compare($item, 'missing', strlen($item)-7, 7) === 0) {
                                return 'missing';
                            }
                        }
                    }
                }
            }
            return 'normal';
        }
    }

    public function restore($inline = false)
    {

        $tmpFile = sys_get_temp_dir() . '/restore.' . generate_uuid() . '.' . $this->basename;
        $cmd = "rdiff-backup --restore-as-of " . escapeshellarg($this->_date == 'all' ? 'now' : $this->_date) . " " . escapeshellarg($this->localPath) . " " . escapeshellarg($tmpFile) . " 2>&1";
        $out = "";
        $output = array();
        $lastLine = exec($cmd, $output, $retval);
        if (file_exists($tmpFile)) {
            //restored
            $contents = file_get_contents($tmpFile);
            @unlink($tmpFile);
            return $contents;
        }else{
            // failed to restore
            foreach ($output as $line) {
                $out .= $line . PHP_EOL;
            }
            throw new NotFoundHttpException('The file could not be restored. ' . PHP_EOL . $out);
        }
    }

    /* TODO weg */
    public function browse($path = '/')
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
        $backup->filesystem->slash('Desktop')->slash('file.txt')->extenstion; // txt

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
