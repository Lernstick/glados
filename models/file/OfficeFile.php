<?php

namespace app\models\file;

use Yii;
use app\models\file\RegularFile;
use app\models\file\FileInterface;
 
class OfficeFile extends RegularFile implements FileInterface
{

    /**
     * @var string command to extract text from pdf
     * @see https://github.com/unoconv/unoconv#problems-running-unoconv-from-nginxapachephp
     */
    public $cmd = "HOME={home} unoconv --stdout -f txt {path}";

    /**
     * @inheritdoc
     */
    public static function endings()
    {
        return ['odt', 'doc', 'docx', 'ppt'];
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
