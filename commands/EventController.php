<?php

namespace app\commands;

use yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\forms\EventItemSend;
use app\models\forms\EventStreamListen;

/**
 * Event Daemon
 * This daemon is to send and recieve event
 */
class EventController extends Controller
{

    /**
     * @var integer the uid under which the script should be executed.
     * defaults to 33, which in debian is the www-data user.
     */
    public $uid = 33;

    /**
     * @var integer the gid under which the script should be executed.
     * defaults to 33, which in debian is the www-data user.
     */
    public $gid = 33;

     /* 
     * @inheritdoc
     */
    public function init()
    {
        # change to www-data user, gid must be set frist
        posix_setgid($this->gid);
        posix_setuid($this->uid);

        # Change process group id
        posix_setpgid(getmypid(), getmypid());
    }

    /**
     * Sends events.
     *
     * @param string $event the type of event to send
     * @param string $data the data payload of the event in json
     * @param int $nrOfTimes how many times the event should be sent
     * @param int $priority the priority
     * @return int exit code
     */
    public function actionSend($event, $data, $nrOfTimes = 1, $priority = 0)
    {
        $args = [
            "EventItemSend" => [
                'event' => $event,
                'priority' => $priority,
                'data' => $data,
                'nrOfTimes' => $nrOfTimes,
            ]
        ];

        $event = new EventItemSend();
        if ($event->load($args)) {
            $event->generateEvents();
        }

        return ExitCode::OK;
    }

}
