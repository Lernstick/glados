<?php

namespace app\components;

use yii\base\Behavior;
use yii\base\Event;

class GrowlBehavior extends Behavior
{

    /**
     * @var bool create growl message for the deletion of an item
     */
    public $notify_deletion = true;

    /**
     * @var string|callable delete message to show
     */
    public $delete_message;

    /**
     * @var string the alert category of the growl element.
     */
    public $category = 'info';

    /**
     * @inheritdoc 
     */
    public function events()
    {
        $ret = [];
        if ($this->notify_deletion) {
            // adds new history records upon deleting the main record
            $ret[\yii\db\ActiveRecord::EVENT_AFTER_DELETE] = 'deleteEvent';
        }

        return $ret;
    }

    /**
     * Creates a history entry for all deleted entries that are in the attributes
     * array.
     * @param Event $event
     */
    public function deleteEvent($event)
    {
        if (is_string($this->delete_message)) {
            $msg = $this->delete_message;
        } else if (is_callable($this->delete_message)) {
            $func = $this->delete_message;
            $msg = $func($event->sender);
        } else {
            $msg = \Yii::t('app', 'Record deleted successfully');
        }
        \Yii::$app->session->addFlash($this->category, $msg);
    }
}