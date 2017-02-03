<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use yii\web\NotFoundHttpException;
use app\models\Daemon;

/**
 * This is the model class for the rdiff filesystem.
 *
 * @property string $location The real path of the backups root dir (where the rdiff-backup-data directory is)
 * @property string $path The parent directory's path
 * @property string $basename Trailing name component of the path
 * @property string $remotePath Real path of the file/dir at the remote system
 * @property string $localPath Real path of the file/dir at the local system
 * @property string $incrementsPath Real path of the file/dir in the increments direcory of rdiffbackup
 * @property array $versions An array of all versions
 * @property string $version Version of the current instance
 * @property RdiffFileSystem|RdiffFileSystem[] $contents
 * @property string $type The file type of the current path
 * @property string $state
 * @property string $displayName Trailing name component of the path to display in the webinterface
 * @property string $realState
 */
class RdiffFileSystem extends Model
{

    /**
     * @var string 
     */
    public $root = '/';

    /**
     * @var string 
     */
    public $restoreUser = 'root';

    /**
     * @var string 
     */
    public $restoreHost = 'localhost';

    /**
     * @var string The regular expression to match rdiffbackup dates
     */
    public $dateRegex = '/[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}\:[0-9]{2}\:[0-9]{2}\+[0-9]{2}\:[0-9]{2}/';

    /**
     * @var string Whether the properties are already populated or not
     */
    public $propertiesPopulated = false;    

    /**
     * @var array A list of file or directory names to omit when reading a directory
     */
    public $excludeDirs = ['.', '..', 'rdiff-backup-data'];    
//    private static $instance;

    /**
     * @var string The current location
     */
    private $_location = '.';

    /**
     * @var array A list of available versions
     */    
    private $_versions = [];

    /**
     * @var string
     */
    private $_pwd = '.'; //evtl: this->_file

    /**
     * @var string Holding the date of the current version
     */
    private $_date = 'now';

    public function init()
    {
        parent::init();
    }

    /**
     * Getter for the location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->_location;
    }

    /**
     * Setter for location
     *
     * @param string $value
     * @return void
     */
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

    /**
     * Change the path of the current RdiffFileSystem instance
     *
     * @param string $path - the relative path to go
     * @return null|RdiffFileSystem - null if the path does not exist in the backup dir
     *                                RdiffFileSystem instance if it exists
     */
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

    /**
     * Populates all properties of the current path if not already done
     *
     * @param bool $force - force the function to repopulate all properties
     * @return void
     */
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

    /**
     * Getter for the versions array
     *
     * @return array
     */
    public function getVersions()
    {
        $this->populateProperties();
        return $this->_versions;
    }

    /**
     * Getter for the current version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_date;
    }

    /**
     * Switch the version of the current RdiffFileSystem instance
     *
     * @param string $date - the version to switch to
     * @return null|RdiffFileSystem - null if the version does not exist in the backup directory
     *                                RdiffFileSystem instance if it exists
     */
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

    /**
     * Getter for contents
     *
     * @return RdiffFileSystem|RdiffFileSystem[]
     *                      An array of RdiffFileSystem[] instances if the current path is a directory
     *                      RdiffFileSystem instance if the current path is a file
     */
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

    /**
     * Getter for the file type of the current path
     *
     * @return string 
     * @see http://php.net/manual/de/function.filetype.php for the different types
     */
    public function getType()
    {
        if (file_exists($this->localPath)) {
            return filetype($this->localPath);
        } else {
            return 'file'; //TODO get filetype via increments
        }
    }

    /**
     * Getter for the state the current path
     *
     * @return string - the two states are: 'normal' if it's a normal file
     *                                      'missing' if the file is a removed file
     */
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

    /**
     * Getter for the file name to display in the webinterface
     *
     * @return string - the file name without the leading .wh. in case of a whiteout file
     * @see http://aufs.sourceforge.net/aufs.html for whiteout files
     */
    public function getDisplayName()
    {
        if (strpos($this->basename, '.wh.') === 0) {
            return substr($this->basename, 4);
        } else {
            return $this->basename;
        }
    }

    /**
     * Determine the real state of a file/dir
     *
     * @return string - the paths real state
     * @see getState()
     */
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

    /**
     * Restores the current file to a temporary file and return its contents to the browser
     *
     * @param bool $inline - whether to return the contents inline or as a download dialog
     * @return data - the contents of the restored file
     * @throws yii\web\NotFoundHttpException if the file cannot be restored
     */
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

}
