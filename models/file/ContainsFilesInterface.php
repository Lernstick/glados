<?php

namespace app\models\file;

use app\models\file\FileInterface;

/**
 * ContainsFilesInterface
 * 
 * ContainsFilesInterface is the interface that should be implemented by classes that
 * represent physical or virtual files that contain files themselves, such as archives.
 */
interface ContainsFilesInterface extends FileInterface
{

    /**
     * The list of files contained in this archive file
     *
     * @return FileInArchive[] array of FileInArchive objects representing the files
     * contained in the archive.
     */
    public function getFiles();

    /**
     * Returns the physical path of the contained file via its path within the archive
     *
     * @param string $path path of the file to extract within the archive
     * @return string|null path on the filesystem to find the physical extracted file
     * or null if the file could not be extracted.
     */
    public function physicalPathOf($path);

}

?>