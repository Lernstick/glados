<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

 
class File extends Component
{

    /**
     * @inheritdoc
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @var array object instances array with key set to $_filepath
     */
    private static $_instances = array();

    /**
     * @var string filesystem object path submitted by user
     */
    public $path;

    /**
     * Returns the instance of File for the specified file.
     *
     * @param string $path Path to file specified by user.
     * @param string $class_name Class name to spawn object for.
     * @return object File instance
     * @throws Exception
     */
    public static function getInstance($path, $class_name = __CLASS__) {
        if ($class_name != __CLASS__ && !is_subclass_of($class_name, __CLASS__)) {
            throw new Exception('Unable to spawn File object from `' . $class_name . '` class');
        }
        if (!array_key_exists($path, self::$_instances)) {
            self::$_instances[$path] = new $class_name(['path' => $path]);
        }
        return self::$_instances[$path];
    }

    /**
     * Basic File method. Sets File object to work with specified filesystem object.
     * Essentially path supplied by user.
     *
     * @param string $path Path to the file specified by user
     * @return object File instance for the specified filesystem object
     * @throws Exception - TODO
     */
    public function set($path) {
        $cl = get_class($this);
        $instance = $cl::getInstance($path);
        $instance->path = $path;
        return $instance;
    }

    /**
     * @return boolean 'True' if file exists, otherwise 'False'
     */
    public function getExists(){
       return file_exists($this->path);
    }

    /**
     * @return int file size in bytes or formatted size or null
     */
    public function getSize ( $format = false )
    {
        if (!$this->exists){
            return null;
        }
        $size = filesize($this->path);
        return $format ? $this->formatSize($size) : $size;
    } 

    /**
     * @return string file size formatted
     */
    public function formatSize($bytes){
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    public function getInfo(){
        $info = $this->exists ? exec('/usr/bin/file -b ' . escapeshellarg($this->path)) : null;
        return $info;
    }


}

