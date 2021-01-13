<?php

namespace app\models\file;

use Yii;
use app\models\file\RegularFile;
use app\models\file\FileInterface;
 
class OfficeFile extends RegularFile implements FileInterface
{

    /**
     * @var string command to extract text from pdf
     */
    #public $cmd = "catdoc {path}";
    public $cmd = "unoconv -f txt --stdout {path}";

    /**
     * for soffice
     * install libreoffice-common default-jre libreoffice-java-common
     * unoconv -f txt Anleitung.docx
     */

    /**
     * @inheritdoc
     */
    public function getToText()
    {
        exec(substitute($this->cmd, ['path' => escapeshellarg($this->physicalPath)]), $output, $retval);
        if ($retval == 0) {
            return $output;
        }
        return null;
    }
}
