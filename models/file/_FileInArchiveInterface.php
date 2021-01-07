<?php

namespace app\models\file;

use app\models\file\FileInterface;

/**
 * FileInArchiveInterface
 * 
 * FileInArchiveInterface is the interface that should be implemented by classes that
 * represent physical or virtual files that are contained in archives.
 */
interface FileInArchiveInterface extends FileInterface
{
    /**
     * Getter
     *
     * @return ZipFile|SquashfsFile or and other object that implements ArchiveInterface 
     * of the archive file containing this file
     */
    public function getArchive();

    /**
     * Setter
     *
     * @return ZipFile|SquashfsFile or and other object that implements ArchiveInterface 
     * of the archive file containing this file
     */
    public function setArchive($value);

}

?>