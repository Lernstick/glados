<?php

namespace app\models\file;

use Yii;
use app\models\file\RegularFile;
use app\models\file\ContainsFilesInterface;
 
class SquashfsFile extends RegularFile implements ContainsFilesInterface
{
    /**
     * @inheritdoc
     */
    public function getFiles()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function physicalPathOf($path)
    {
        return null;
    }

}
