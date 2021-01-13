<?php

namespace app\models\file;

use Yii;
use app\models\file\RegularFile;
use app\models\file\ContainsFilesInterface;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;
use app\components\ElasticsearchBehavior;

class SquashfsFile extends RegularFile implements ContainsFilesInterface
{

    /**
     * @var string path to the unzip binary
     */
    public $binary = '/usr/bin/unsquashfs';

    /**
     * @var array file information array
     */
    private $_file_info = [];

    /**
     * @var string the temporary path of the zip contents
     */
    private $_tmpdir;

    /**
     * @inheritdoc 
     */
    public function behaviors()
    {
        return [
            'ExamSquashfs' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => 'file',
                'allModels' => function($class) { return ArrayHelper::getColumn(\app\models\Exam::find()->all(), 'squashfsFile'); },
                'onlyIndexIf' => function($m) { return $m->exists; },
                'fields' => [
                    'path',
                    'mimetype',
                    'content' => function($m) { return $m->toText; },
                    'size',
                    'exam' => function($m) { return $m->relation->id; },
                    'user' => function($m) { return $m->relation->user_id; },
                ],
                // mapping of elasticsearch
                'mappings' => [
                    'properties' => [
                        'path'     => ['type' => 'text'],
                        'mimetype' => ['type' => 'text'],
                        'content' =>  ['type' => 'text'],
                        'size'     => ['type' => 'integer'],
                        'exam'     => ['type' => 'integer'],
                        'user'     => ['type' => 'integer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->physicalPath;
    }

    /**
     * @inheritdoc
     */
    public function getFiles()
    {
        $files = [];
        foreach (ArrayHelper::getColumn($this->fileInfo, 'path') as $path) {
            $files[] = new FileInArchive([
                'path' => $path,
                'archive' => $this,
            ]);
        }
        return $files;
    }

    /**
     * @inheritdoc
     */
    public function physicalPathOf($path)
    {
        $physicalPath = FileHelper::normalizePath($this->tmpdir . '/' . $path);
        $this->extract();
        if (file_exists($physicalPath)) {
            return $physicalPath;
        }
        return null;
    }

    /**
     * Getter for the temporary directory
     *
     * @return string the path
     */
    public function getTmpdir()
    {
        if (empty($this->_tmpdir)) {
            $this->_tmpdir = FileHelper::normalizePath(\Yii::$app->params['tmpPath'] . '/' . generate_uuid());
        }
        return $this->_tmpdir;
    }

    /**
     * Extracts the archive
     *
     * @return bool success or failure
     */
    public function extract()
    {
        if (!file_exists($this->tmpdir)) {
            FileHelper::removeDirectory($this->tmpdir);
            exec(substitute('{binary} -d {dir} {path} 2>/dev/null', [
                'binary' => $this->binary,
                'path' => escapeshellarg($this->physicalPath),
                'dir' => escapeshellarg($this->tmpdir),
            ]), $output, $retval);
            return $retval;
        }
        return true;
    }

    /**
     * Generates an array with file information about every file in the squash filesystem
     *
     * @return array the file list. The array has the following structure, for example:
     *      [
     *          [
     *              'mode' => '-rwxr-wr-x',
     *              'owner' => 'root',
     *              'group' => 'root',
     *              'size' => 27,
     *              'date' => '2015-03-13',
     *              'time' => '16:56',
     *              'path' => 'squashfs-root/home/user/file'
     *          ],
     *      ]
     */
    public function getFileInfo()
    {

        if (empty($this->_file_info) && $this->exists) {
            exec(substitute('{binary} -ll {path}', [
                'binary' => $this->binary,
                'path' => escapeshellarg($this->physicalPath),
            ]), $output, $retval);

            for ($i=3;$i<=count($output);$i++){
                if(!array_key_exists($i, $output)){
                    break;
                }

                list(
                    $a['mode'],
                    $a['owner'],
                    $a['group'],
                    $a['size'],
                    $a['date'],
                    $a['time'],
                    $a['path'],
                ) = preg_split('/[\s,\/]+/', $output[$i], 7, PREG_SPLIT_NO_EMPTY);
                $a['path'] = FileHelper::normalizePath(substr($a['path'], strlen('squashfs-root')));

                $this->_file_info[] = $a;
            }
        }
        return $this->_file_info;
    }

    /**
     * @inheritdoc
     */
    public function __destruct() {
        if (file_exists($this->tmpdir)) {
            FileHelper::removeDirectory($this->tmpdir);
        }
    }
}
