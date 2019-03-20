<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use yii\web\NotFoundHttpException;
use yii\helpers\FileHelper;
 
class Zip extends File
{

    /**
     * @inheritdoc
     *
     * @throws NotFoundHttpException if the unsquashfs binary could not be found or if the
     *         file is not executable.     
     */
    public function __construct()
    {
        parent::__construct();
        
        if (!file_exists('/usr/bin/unzip') || !is_executable('/usr/bin/unzip')) {
            throw new NotFoundHttpException('/usr/bin/unzip: No such file or directory or the binary is not executable. Please install unzip.');
        }
    }

    /**
     * @inheritdoc
     */
    public static function getInstance($path, $class_name = __CLASS__)
    {
        return parent::getInstance($path, $class_name);
    }

    /**
     * Generates an array with file information about every file in the zip file
     *
     * @return array the file list. The array has the following structure, for example:
     *      [
     *          [
     *              'length' => 312,
     *              'method' => Stored,
     *              'size' => 123,
     *              'cmpr' => '18%',
     *              'date' => '2015-03-13',
     *              'time' => '16:56',
     *              'crc32' => 'd73d1fd1',
     *              'path' => 'home/user/file'
     *          ],
     *      ]
     */
    public function getFileList()
    {
        exec('/usr/bin/unzip -v ' . escapeshellarg($this->path), $output, $retval);
        for ($i=3;$i<=count($output) - 3;$i++){
            if(!array_key_exists($i, $output)){
                break;
            }

            list(
                $a['length'],
                $a['method'],
                $a['size'],
                $a['cmpr'],
                $a['date'],
                $a['time'],
                $a['crc32'],
                $a['path'],
            ) = preg_split('/[\s]+/', $output[$i], 8, PREG_SPLIT_NO_EMPTY);

            $a['path'] = FileHelper::normalizePath("/" . $a['path']);
            $file_list[] = $a;
        }
        return $this->exists ? $file_list : null;
    }

    public function file_exists($path)
    {
        foreach ($this->fileList as $file) {
            if ($file['path'] == FileHelper::normalizePath('/' . $path)) {
                return true;
            }
        }
        return false;
    }

}

