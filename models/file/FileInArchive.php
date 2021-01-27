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
                'index' => ['class' => '\app\models\indexes\FileIndex'],
                'allModels' => [
                    'foreach' => function($class) { return ArrayHelper::getColumn(\app\models\Exam::find()->all(), 'zipFile'); },
                    'allModels' => function($zipFile) { return $zipFile->files; },
                ],
                'onlyIndexIf' => function($m) { return $m->onlyIndexIf(); },
                'fields' => [
                    'path',
                    'filename' => function($m) { return basename($m->path); },
                    'directory' => function($m) { return dirname($m->path); },
                    'mimetype',
                    'content' => function($m) { return $m->toText; },
                    'size',
                    'archive' => function($m) { return $m->archive->path; },
                    'exam' => function($m) { return $m->archive->relation->id; },
                    'user' => function($m) { return $m->archive->relation->user_id; },
                ],
            ],
            'ExamSquashfsContents' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => ['class' => '\app\models\indexes\FileIndex'],
                'allModels' => [
                    'foreach' => function($class) { return ArrayHelper::getColumn(\app\models\Exam::find()->all(), 'squashfsFile'); },
                    'allModels' => function($squashfsFile) { return $squashfsFile->files; },
                ],
                'onlyIndexIf' => function($m) { return $m->onlyIndexIf(); },
                'fields' => [
                    'path',
                    'mimetype',
                    'content' => function($m) { return $m->toText; },
                    'size',
                    'archive' => function($m) { return $m->archive->path; },
                    'exam' => function($m) { return $m->archive->relation->id; },
                    'user' => function($m) { return $m->archive->relation->user_id; },
                ],
            ],
        ];
    }

    /**
     * @return bool only index the file if it matches one of the file endings
     */
    public function onlyIndexIf()
    {
        // only if the file is a dot-file or inside a dot-dir
        if (strstr($this->path, '/.') === false) {
            $only_index = ['odt', 'pdf', 'txt', 'doc', 'docx', 'ppt', 'zip'];
            $ext = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
            return in_array($ext, $only_index);
        }
        return false;
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
