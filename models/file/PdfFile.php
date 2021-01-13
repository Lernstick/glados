<?php

namespace app\models\file;

use Yii;
use app\models\file\RegularFile;
use app\models\file\FileInterface;
 
class PdfFile extends RegularFile implements FileInterface
{

    /**
     * @var string command to extract text from pdf
     */
    public $cmd = "pdftotext {path} -";

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
