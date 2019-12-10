<?php

namespace app\models;

/**
 * DaemonInterface
 * 
 * DaemonInterface is the interface that should be implemented by classes who support
 * looping trough items and then locking them before processing.
 */
interface DaemonInterface
{

    /**
     * Finds the next item to be processed.
     *
     * @return object|null the item object that has to be processed next or null if no next
     * object is in the pipeline
     */
    public function getNextItem();

    /**
     * Processes an item.
     *
     * @param object $item the item which has been locked and can now be processed
     */
    public function processItem($item);

    /**
     * Locks an item for processing.
     *
     * @param object $item the item which should be locked for processing
     * @return bool whether locking was successful or not
     */
    public function lockItem($item);

    /**
     * Unlocks an item after processing.
     *
     * @param object $item the item which should be unlocked for processing
     * @return bool whether locking was successful or not
     */    
    public function unlockItem($item);

    /**
     * This is the actual job of the daemon.
     *
     * @param mixed $args all sorts of arguments can be given to that function.
     */
    public function doJob();

    /**
     * This is the actual job of the daemon, but just one iteration.
     *
     * @param mixed $args all sorts of arguments can be given to that function.
     */
    public function doJobOnce();
}

?>