<?php

namespace app\models\file;
 
use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use app\models\file\FileInterface;

class RegularFile extends Model implements FileInterface
{

    /**
     * @var mixed object to which the file is related to.
     */
    public $relation;

    /**
     * @var array possible file models.
     */
    public $types = [
        '\app\models\file\TextFile',
        '\app\models\file\PdfFile',
        '\app\models\file\OfficeFile',
        '\app\models\file\ZipFile',
        '\app\models\file\SquashfsFile',
        '\app\models\file\ImageFile',
    ];

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

        foreach ($this->types as $class) {
            foreach ($class::endings() as $ending) {
                if (StringHelper::endsWith($this->path, '.'.$ending)) {
                    return (new $class(['path' => $this->physicalPath]))->toText;
                }
            }
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function endings()
    {
        return [];
    }

}
