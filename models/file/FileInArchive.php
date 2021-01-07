<?php

namespace app\models\file;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use app\models\file\RegularFile;
use app\models\file\FileInterface;
use app\components\ElasticsearchBehavior;

class FileInArchive extends RegularFile implements FileInterface
{
    /**
     * @var ZipFile|SquashfsFile or and other object that implements ContainsFilesInterface 
     * of the archive file containing this file
     */
    public $archive;

    /**
     * @inheritdoc 
     */
    public function behaviors()
    {
        return [
            'ExamZipContents' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => 'file',
                'allModels' => [
                    'foreach' => function($class) { return ArrayHelper::getColumn(\app\models\Exam::find()->all(), 'zipFile'); },
                    'allModels' => function($zipFile) { return $zipFile->files; },
                ],
                'onlyIndexIf' => function($m) { return $m->fileMatches(); },
                'fields' => [
                    'path',
                    'mimetype',
                    'content' => function($m) { return $m->toText; },
                    'size',
                    'archive' => function($m) { return $m->archive->path; },
                    'exam' => function($m) { return $m->archive->relation->id; },
                    'user' => function($m) { return $m->archive->relation->user_id; },
                ],
                // mapping of elasticsearch
                'mappings' => [
                    'properties' => [
                        'path'     => ['type' => 'text'],
                        'mimetype' => ['type' => 'text'],
                        'content'  => ['type' => 'text'],
                        'size'     => ['type' => 'integer'],
                        'archive'  => ['type' => 'text'],
                        'exam'     => ['type' => 'integer'],
                        'user'     => ['type' => 'integer'],
                    ],
                ],
            ],
            'ExamSquashfsContents' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => 'file',
                'allModels' => [
                    'foreach' => function($class) { return ArrayHelper::getColumn(\app\models\Exam::find()->all(), 'squashfsFile'); },
                    'allModels' => function($squashfsFile) { return $squashfsFile->files; },
                ],
                'onlyIndexIf' => function($m) { return $m->fileMatches(); },
                'fields' => [
                    'path',
                    'mimetype',
                    'content' => function($m) { return $m->toText; },
                    'size',
                    'archive' => function($m) { return $m->archive->path; },
                    'exam' => function($m) { return $m->archive->relation->id; },
                    'user' => function($m) { return $m->archive->relation->user_id; },
                ],
                // mapping of elasticsearch
                'mappings' => [
                    'properties' => [
                        'path'     => ['type' => 'text'],
                        'mimetype' => ['type' => 'text'],
                        'content'  => ['type' => 'text'],
                        'size'     => ['type' => 'integer'],
                        'archive'  => ['type' => 'text'],
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
    public function fileMatches()
    {
        return StringHelper::endsWith($this->path, '.txt') && strstr($this->path, '/.') === false;
    }


    /**
     * @inheritdoc
     */
    public function getPhysicalPath()
    {
        return $this->archive->physicalPathOf($this->path);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->archive->path . ':' . $this->path;
    }

}
