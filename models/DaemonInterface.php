<?php

namespace app\models;

interface DaemonInterface
{

    /**
     * Finds the next to be processed item.
     * @return object|null the item object that has to be processed next or null if no next
     * object is in the pipeline
     */
    public function getNextItem();

    /**
     * Processes an item.
     * @param object $item the item which has been locked and can now be processed
     */
    public function processItem($item);

    /**
     * Locks an item.
     * @param object $item the item which should be locked for processing
     * @return bool whether locking was successful or not
     */
    public function lockItem($item);

    /**
     * Unlocks an item.
     * @param object $item the item which should be unlocked for processing
     * @return bool whether locking was successful or not
     */    
    public function unlockItem($item);
}

?>