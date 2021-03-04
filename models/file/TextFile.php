<?php

namespace app\models\file;

use Yii;
use app\models\file\RegularFile;
use app\models\file\FileInterface;
 
class TextFile extends RegularFile implements FileInterface
{

    /**
     * @inheritdoc
     */
    public static function endings()
    {
        return ['txt', 'md'];
    }

    /**
     * @inheritdoc
     */
    public function getToText()
    {
        return $this->contents;
    }
}
