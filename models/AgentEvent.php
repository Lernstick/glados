<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\EventItem;

/**
 * This is the model class for agent events.
 *
 */
class AgentEvent extends EventItem
{

    /**
     * @var Ticket the ticket.
     */
    private $_ticket;

    /**
     * Getter for the associated ticket.
     * 
     * @return Ticket the ticket
     */
    public function getTicket()
    {
        return $this->_ticket;
    }

    /**
     * Setter for the associated ticket.
     * 
     * @param Ticket $ticket the ticket
     * @return void
     */
    public function setTicket($ticket)
    {
        $this->event = 'agent/' . $ticket->token;
        $this->_ticket = $ticket;
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        // if the agent is online, just send an event
        if ($this->ticket->agent_online) {
            return parent::generate();

        // fallback if the agent is not online
        } else {
            // set up the command
            $cmd = 'lernstick-exam-agent ' . escapeshellarg($this->eventAsJson);
            $this->ticket->runCommandAsync($cmd);
        }
        
    }

    /**
     * @todo
     */
    public function getEventAsJson()
    {
        return json_encode([
            'id' => "None",
            'event' => $this->event,
            'data' => $this->data,
        ]);
    }

}
