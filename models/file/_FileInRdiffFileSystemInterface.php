<?php

namespace app\models\file;

use app\models\file\FileInterface;

/**
 * FileInRdiffFileSystemInterface
 * 
 * FileInRdiffFileSystemInterface is the interface that should be implemented by classes that
 * represent physical or virtual files that are contained in a RdiffFileSystem instance.
 */
interface FileInRdiffFileSystemInterface extends FileInterface
{
    /**
     * Returns the RdiffFileSystem instance of the Rdiff directory.
     * 
     * @return RdiffFileSystem or and other object that implements ArchiveInterface
     */
    public function getRdiffFileSystem();
}

?>