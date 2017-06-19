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
 * @property string $mode
 * @property string $size 
 * @property string $uid 
 * @property string $gid 
 * @property string $user 
 * @property string $group 
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
    private $_propertiesPopulated = false;    

    /**
     * @var string Whether the file infos are already populated or not
     */
    private $_fileInfoPopulated = false;    

    public $options = [
        'showDotFiles' => false,
    ];

    /**
     * @var array A list of file or directory names to omit when reading a directory
     */
    public $excludeList = [
        #'/^\./',                    // exclude all dotfiles
        '/^\.$/',
        '/^\.\.$/',        
    ];

    /**
     * @var array A list of file or directory paths to omit when reading a directory
     */
    public $excludePaths = [
        '/^\/rdiff-backup-data$/',      // exclude the rdiff-backup-data directory    
        '/^\/Screenshots$/',
        '/^\/shutdown$/',
        '/^\/Schreibtisch\/finish_exam.desktop$/',
    ];

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

    private $_version;

    private $_fileInfo;

    private $_newestBackupVersion;

    public function init()
    {
        if (boolval($this->options['showDotFiles']) === false) {
            array_unshift($this->excludeList, '/^\./');
        }

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
        return FileHelper::normalizePath($this->location . '/rdiff-backup-data/increments/' . $this->_pwd);
    }    

    public function getRdiffBackupDataDir()
    {
        return FileHelper::normalizePath($this->location . '/rdiff-backup-data/');
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
        $this->_propertiesPopulated = false;

        if (count($this->versions) != 0) {
            return $this;
        }else{
            //throw new NotFoundHttpException($this->path . ': No such file or directory.');
            return null;
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
        if ($this->_propertiesPopulated !== true || $force === true) {

            $versions = [];

            if ($this->path == "") {
                /* only scan rdiff-backup-data dir if it exists */
                if (is_dir($this->location . '/rdiff-backup-data')) {
                    $list = array_filter(scandir($this->location . '/rdiff-backup-data'), array($this, 'filterExclude'));
                    foreach ($list as $item) {
                        if (@strpos($item, 'increments') === 0) {
                            if (preg_match($this->dateRegex, $item, $matches) === 1) {
                                $versions[] = $matches[0];
                            }
                        }
                    }
                }
            } else {
                /* only scan increments dir if it exists */
                if (is_dir(dirname($this->incrementsPath))) {
                    /* get all version in the increments path */
                    $list = array_filter(scandir(dirname($this->incrementsPath)), array($this, 'filterExclude'));
                    foreach ($list as $item) {
                        if (@strpos($item, $this->basename) === 0) {
                            if (preg_match($this->dateRegex, $item, $matches) === 1) {
                                $versions[] = $matches[0];
                            }
                        }
                    }
                }
            }

            /* add the current version, if available */
            if (file_exists($this->localPath)) {
                $versions[] = $this->newestBackupVersion;
            }/* else {
                $versions[] = 'now';                
            }*/

            $this->_versions = array_unique($versions);
            rsort($this->_versions);
            $this->_propertiesPopulated = true;
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
        if ($this->_date == 'all') {

            $a = new RdiffFileSystem([
                'root' => $this->root,
                'location' => $this->location,
                'restoreUser' => $this->restoreUser,
                'restoreHost' => $this->restoreHost,
                'options' => $this->options,
            ]);

            foreach ($this->versions as $version) {
                if ($a->slash($this->path)->versionAt($version)->state == 'normal') {
                    $this->_version = $version;
                    return $version;
                }
            }
        } else if ($this->_date == "now") {
            $this->_version = $this->newestBackupVersion;
            //var_dump(end(glob($this->rdiffBackupDataDir . "/file_statistics.*.*")));
        } else {
            $this->_version = $this->_date;
        }

        return $this->_version;
    }

    /**
     * @return string|false the newest backup version as RFC date
     */
    public function getNewestBackupVersion()
    {
        $file = end(glob($this->rdiffBackupDataDir . "/session_statistics.*.data"));
        if (preg_match($this->dateRegex, $file, $matches)) {
            return $matches[0];
        }
        return false;
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
     * Filter for array_filter() to exclude specific file names and paths
     *
     * @param string $name - the file name
     * @return bool        - whether to keep the name or not
     */
    private function filterExclude($name)
    {
        if (preg_match($this->dateRegex, $name, $matches) === 1) {
            $name = current(explode("." . $matches[0], $name));
        }
        $path = FileHelper::normalizePath($this->_pwd . '/' . $name);

        foreach ($this->excludeList as $pattern) {
            if (preg_match($pattern, $name) === 1) {
                return false;
            }
        }
        foreach ($this->excludePaths as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return false;
            }
        }
        return true;
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
            $contents = [];
            foreach ($this->versions as $version) {
                $a = new RdiffFileSystem([
                    'root' => $this->root,
                    'location' => $this->location,
                    'restoreUser' => $this->restoreUser,
                    'restoreHost' => $this->restoreHost,
                    'options' => $this->options,
                ]);
                $a->slash($this->path)->versionAt($version);
                $contents[] = $a;
            }
            return $contents;
        } else if ($this->type == 'dir') {

            $listLocal = [];
            if (file_exists($this->localPath)) {
                if ($this->version == $this->newestBackupVersion || $this->_date == 'all') {
                    // find current files
                    $listLocal = array_filter(scandir($this->localPath), array($this, 'filterExclude'));
                }
            }

            $listInc = [];
            if ($this->_date != 'now') {
                // find files in the inrements dir
                if (file_exists($this->incrementsPath)){
                    $list = array_filter(scandir($this->incrementsPath), array($this, 'filterExclude'));
                } else {
                    $list = [];
                }

                foreach ($list as $key => $item) {
                    if ($this->_date == 'all') {
                        if (preg_match($this->dateRegex, $item, $matches) === 1) {
                            $listInc[] = current(explode("." . $matches[0], $item));
                        } else {
                            $listInc[] = $item;
                        }
                    } else {
                        if (strpos($item, $this->version) !== false) {
                        //if (preg_match($this->_date, $item, $matches) === 1) {
                            $listInc[] = current(explode("." . $this->version, $item));
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
                    'options' => $this->options,
                ]);
                #var_dump($this->path . '/' . $item);die();
                $fs->slash($this->path . '/' . $item)->versionAt($this->_date);
                $contents[] = $fs;
            }
            return $contents;
        }
    }

    /**
     * Getter for the file type of the current path
     *
     * @return string|false
     * @see http://php.net/manual/de/function.filetype.php for the different types
     */
    public function getType()
    {
        if (file_exists($this->localPath)) {
            return filetype($this->localPath);
        } else if (file_exists($this->incrementsPath)) {
            return filetype($this->incrementsPath);
        } else {
            return 'file';
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
     * Populates all file info of the current path if not already done
     *
     * @param bool $force - force the function to repopulate
     * @return void
     */
    private function populateFileInfo($force = false)
    {

        if ($this->_fileInfoPopulated === false || $force === true) {
            $path = ltrim($this->path, '/');
            if (($file = current(glob($this->rdiffBackupDataDir . '/mirror_metadata.' . $this->version . ".*"))) !== false) {
                $lines = gzfile($file);
                for ($i=0; $i < count($lines); $i++) {
                    if ("File " . $path == trim($lines[$i])) {
                        $start = $i;
                        $end = count($lines);
                        for ($e=$i+1; $e < count($lines); $e++) {
                            if (strpos(trim($lines[$e]), 'File ') === 0) {
                                $end = $e;
                                break;
                            }
                        }
                        break;
                    }
                }
                if (isset($start) && isset($end)) {
                    foreach (array_slice($lines, $start, $end-$start) as $line) {
                        $stat = explode(" ", trim($line));
                        $this->_fileInfo[$stat[0]] = $stat[1];
                    }
                }
                $this->_fileInfoPopulated = true;
            }
        }
    }

    /**
     * @return integer|null uid
     */
    public function getUid()
    {
        $this->populateFileInfo();
        return isset($this->_fileInfo["Uid"]) ? $this->_fileInfo["Uid"] : null;
    }

    /**
     * @return integer|null gid
     */
    public function getGid()
    {
        $this->populateFileInfo();
        return isset($this->_fileInfo["Gid"]) ? $this->_fileInfo["Gid"] : null;
    }

    /**
     * @return string|null permissions of the file in octal notation
     */
    public function getUser()
    {
        $this->populateFileInfo();
        return isset($this->_fileInfo["Uname"]) ? $this->_fileInfo["Uname"] : null;
    }

    /**
     * @return string|null permissions of the file in octal notation
     */
    public function getGroup()
    {
        $this->populateFileInfo();
        return isset($this->_fileInfo["Gname"]) ? $this->_fileInfo["Gname"] : null;
    }

    /**
     * @return integer|null permissions of the file in octal notation
     */
    public function getMode()
    {
        $this->populateFileInfo();
        return isset($this->_fileInfo["Permissions"]) ? decoct($this->_fileInfo["Permissions"]) : null;
    }

    /**
     * @return integer|null the size of the file in bytes or null it ot cannot be determined
     */
    public function getSize()
    {
        $this->populateFileInfo();
        return isset($this->_fileInfo["Size"]) ? $this->_fileInfo["Size"] : null;
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
     * @return string - the real state
     * @see getState()
     */
    private function getRealState()
    {

        $this->populateFileInfo();

        if ($this->path == '/' || $this->path == ''){
            return 'normal';
        }

        if (isset($this->_fileInfo["Type"])) {
            if ($this->_fileInfo["Type"] == "None") {
                return 'missing';
            } else {
                return 'normal';
            }
        } else if (is_dir(dirname($this->incrementsPath))) {

            foreach (array_filter(scandir(dirname($this->incrementsPath)), array($this, 'filterExclude')) as $item) {

                if (strpos($item, $this->basename) === 0) {
                    if (preg_match($this->dateRegex, $item, $matches) === 1) {
                        if ($matches[0] == $this->version) {
                            if (substr_compare($item, 'missing', strlen($item)-7, 7) === 0) {
                                return 'missing';
                            } else {
                                return 'normal';
                            }
                        }
                    }
                }
            }
        }
        return 'unknown';
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
