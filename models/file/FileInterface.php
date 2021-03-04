<?php

namespace app\models\file;

/**
 * FileInterface
 * 
 * FileInterface is the interface that should be implemented by classes that
 * represent physical or virtual files on the system.
 */
interface FileInterface
{

    /**
     * Returns whether the file represented by the object exists or not.
     * 
     * @return boolean
     */
    public function getExists();

    /**
     * The real physical absolute path of the file on the filesystem of the system.
     * 
     * @return string|null the absolute path or null if the file is not represented on the system
     */
    public function getPhysicalPath();

    /**
     * The (virtual or physical) absolute path of the file.
     * 
     * @return string the absolute path
     */
    public function getPath();

    /**
     * Setter for the path.
     *
     * @param $value string the path
     * @return void
     */
    public function setPath($value);


    /**
     * The mimetype of the file.
     * 
     * @return string|null mimetype or null if the file does not exist
     */
    public function getMimeType();

    /**
     * Information about the file.
     * @see https://www.php.net/manual/en/function.stat.php
     * 
     * @return array|null information as array null if the file does not exist
     */
    public function getStat();

    /**
     * The file size in bytes.
     * 
     * @return int|null size or null if the file does not exist
     */
    public function getSize();

    /**
     * The contents of the file in whatever format the file provides.
     * 
     * @return mixed the contents or null if the file does not exist
     */
    public function getContents();

    /**
     * The contents of the file in UTF8 text format.
     * 
     * @return string|array|null the contents as string or array line by line  or null 
     * if the contents cannot be translated to text or if the file does not exist
     */
    public function getToText();

    /**
     * Returns a list of file endings that match this file type.
     * 
     * @return array file endings
     */
    public static function endings();

}

?>