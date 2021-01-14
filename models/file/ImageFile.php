<?php

namespace app\models\file;

use Yii;
use app\models\file\RegularFile;
use app\models\file\FileInterface;
 
class ImageFile extends RegularFile implements FileInterface
{

    /**
     * @var string command to extract text from image
     */
    public $cmd = "tesseract {path} -";

    /**
     * @inheritdoc
     */
    public static function endings()
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
    }

    /**
     * @inheritdoc
     */
    public function getToText()
    {
        exec(substitute($this->cmd, [
            'home' => escapeshellarg(\Yii::$app->params['tmpPath']),
            'path' => escapeshellarg($this->physicalPath),
        ]), $output, $retval);
        if ($retval == 0) {
            return array_values(array_filter($output)); # removes empty array elements
        }
        return null;
    }
}
