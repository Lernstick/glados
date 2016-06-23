<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

 
class Squashfs extends File
{

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public static function getInstance($path, $class_name = __CLASS__)
    {
        return parent::getInstance($path, $class_name);
    }

#    public function getIsSquashfs
#    (
#        return strpos($this->info, 'Squashfs') === false ? false : true;
#    }

    public function getFileList()
    {
        exec('/usr/bin/unsquashfs -ll ' . escapeshellarg($this->path), $output, $retval);
        for ($i=3;$i<=count($output);$i++){
            if(!array_key_exists($i, $output)){
                break;
            }
            #var_dump(preg_split('/[\s,\/]+/', $output[$i], 7, PREG_SPLIT_NO_EMPTY));
            list(
                $a['mode'],
                $a['owner'],
                $a['group'],
                $a['compressed_size'],
                $a['date'],
                $a['time'],
                $a['path'],
            ) = preg_split('/[\s,\/]+/', $output[$i], 7, PREG_SPLIT_NO_EMPTY);

            $file_list[] = $a;
        }
        return $this->exists ? $file_list : null;
    }

}

