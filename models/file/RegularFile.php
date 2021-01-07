<?php

namespace app\models\file;
 
use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use app\models\file\FileInterface;

class RegularFile extends Model implements FileInterface
{

    /**
     * @var mixed object to which the file is related to.
     */
    public $relation;

    /**
     * @var string the path of the file in the filesystem.
     */
    private $_path;

    /**
     * @inheritdoc
     */
    public function getExists()
    {
        return file_exists($this->physicalPath);
    }

    /**
     * @inheritdoc
     */
    public function getPhysicalPath()
    {
        return $this->_path;
    }

    /**
     * @inheritdoc
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @inheritdoc
     */
    public function setPath($value)
    {
        $this->_path = FileHelper::normalizePath($value);
    }

    /**
     * @inheritdoc
     */
    public function getMimeType()
    {
        if (!$this->exists){
            return null;
        }
        return mime_content_type($this->physicalPath);
    }

    /**
     * @inheritdoc
     */
    public function getStat()
    {
        if (!$this->exists){
            return null;
        }
        return stat($this->physicalPath);
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        if (!$this->exists){
            return null;
        }
        return filesize($this->physicalPath);
    }

    /**
     * @inheritdoc
     */
    public function getContents()
    {
        if (!$this->exists){
            return null;
        }
        return file_get_contents($this->physicalPath);
    }

    /**
     * @inheritdoc
     */
    public function getToText()
    {
        return null;
    }

}
