<?php

namespace app\commands;

use yii;
use app\commands\DaemonController;
use yii\db\Expression;
use app\models\Ticket;
use app\models\Activity;
use yii\helpers\Console;
use app\models\DaemonInterface;
use app\components\ShellCommand;
use app\models\Issue;

/**
 * Unlocker
 *
 * This daemon unlocks tickets that are trapped in a locked state and will
 * not recover without an action. 
 * Tickts in the bootup_lock = 1 state, that somehow fail to give notice after they
 * successfully booted up, will be unlocked by this command.
 */
class UnlockController extends DaemonController implements DaemonInterface
{

    /**
     * @var int interval in which the tickets should be unlocked (in seconds)
     */    
    public $unlockInterval = 120;

    /**
     * @var string The user to login at the target system
     */
    public $remoteUser = 'root';

    /**
     * @var int timestamp when the tickets were unlocked the last time 
     */    
    private $unlocked = null;

    private $_cmd;

    /**
     * @inheritdoc
     */
    public function start()
    {
        parent::start();
    }

    /**
     * @inheritdoc
     */
    public function doJobOnce ($id = '')
    {
        $this->processItem();
    }

    /**
     * @inheritdoc
     */
    public function doJob ($id = '')
    {
        $this->calcLoad(0);
        while (true) {
            $this->calcLoad(0);
            if ($this->getNextItem()){
                $this->processItem();
                $this->calcLoad(1);
            }

            pcntl_signal_dispatch();
            sleep(rand(5, 10));
            $this->calcLoad(0);
        }
    }

    /**
     * @inheritdoc
     *
     * Unlock all bootup_lock tickets, where the download has finished more
     * than 2 minutes ago, but not longer than one hour ago.
     */
    public function processItem ($item = null)
    {

        // Unlock blocked bootup_lock tickets
        $query = Ticket::find()
            ->where(['bootup_lock' => 1])
            // ticket should be in running state
            ->andWhere(['not', ['start' => null]])
            ->andWhere(['end' => null])
            ->andWhere(['not', ['ip' => null]])
            // the download phase should be done already
            ->andWhere(['not', ['download_request' => null]])
            ->andWhere(['not', ['download_finished' => null]])
            ->andWhere([
                '>=',
                new Expression('unix_timestamp(`download_finished`)'),
                new Expression('unix_timestamp(`download_request`)')
            ])
            // the download should be finished for more than 2 minutes
            // but if it's longer than 1 hour, leave it
            ->andWhere([
                'between',
                'download_finished',
                new Expression('NOW() - INTERVAL 60 MINUTE'),
                new Expression('NOW() - INTERVAL 2 MINUTE')
            ]);

        $tickets = $query->all();
        foreach ($tickets as $ticket) {
            if ($this->lockItem($ticket)) {

                $now = new \DateTime('now'); // now
                $then = new \DateTime('now -60 seconds');

                $query = Activity::find()->where(['ticket_id' => $ticket->id]);
                $query->andFilterWhere(['between', 'date', $then->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')]);
                $query->andFilterHaving(['description' => [
                    '"Bootup successful" message was not recieved, but client is successfully booted up. Client cannot be unlocked. Error: {error}',
                    '"Bootup successful" message was not recieved for a long time. Client is not reachable via its ip address {ip}. For more information, please check the {logfile}.'
                ]]);

                // only trigger if there was no error message in the last 60 seconds
                if ($query->count() == 0) {

                    $this->_cmd = substitute('ssh -v -i {path}/rsa -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o ConnectTimeout={timeout} {user}@{ip} {cmd}', [
                        'path' => \Yii::$app->params['dotSSH'],
                        'timeout' => 10,
                        'user' => $this->remoteUser,
                        'ip' => $ticket->ip,
                        'cmd' => escapeshellarg('LC_ALL=C [ -e /booted ]'),
                    ]);

                    $this->logInfo('Executing command: ' . $this->_cmd);

                    $cmd = new ShellCommand($this->_cmd);
                    $output = "";
                    $date = date('c');
                    $logFile = substitute('{path}/{type}.{token}.{date}.log', [
                        'path' => Yii::getAlias('@runtime/logs'),
                        'type' => 'unlock',
                        'token' => $ticket->token,
                        'date' => $date,
                    ]);

                    $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use (&$output, $logFile) {
                        echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
                        $output .= $event->line;
                        file_put_contents($logFile, $event->line, FILE_APPEND);
                    });

                    $retval = $cmd->run();

                    if ($retval != 0) {

                        Issue::markAs(Issue::CLIENT_OFFLINE, $ticket->id);

                        $logfile = substitute('{url:logfile:log:view:type={type},token={token},date={date}}', [
                            'type' => 'unlock',
                            'token' => $ticket->token,
                            'date' => $date,
                        ]);

                        $act = new Activity([
                                'ticket_id' => $ticket->id,
                                'description' => yiit('activity', '"Bootup successful" message was not recieved for a long time. Client is not reachable via its ip address {ip}. For more information, please check the {logfile}.'),
                                'description_params' => [
                                    'ip' => $ticket->ip,
                                    'logfile' => $logfile,
                                ],
                                'severity' => Activity::SEVERITY_CRITICAL,
                        ]);
                        $act->save();

                        $ticket->client_state = '"Bootup successful" message was not recieved for a long time. Client is not reachable via its ip address {ip}. For more information, please check the {logfile}.';
                        $ticket->client_state_params = [
                            'ip' => $ticket->ip,
                            'logfile' => $logfile,
                        ];
                        $ticket->save(false);

                        $this->logInfo(substitute('Unlocking ticket {token} failed, command "{cmd}" returned exit code {retval} and output: {output}', [
                            'token' => $ticket->token,
                            'output' => $output,
                            'retval' => $retval,
                            'cmd' => $this->_cmd,
                        ]), true, true, true);

                    } else {

                        Issue::markAsSolved(Issue::CLIENT_OFFLINE, $ticket->id);

                        $ticket->bootup_lock = 0;
                        if ($ticket->save()) {
                            $act = new Activity([
                                    'ticket_id' => $ticket->id,
                                    'description' => yiit('activity', '"Bootup successful" message was not recieved, but client is successfully booted up. Client is now unlocked.'),
                                    'severity' => Activity::SEVERITY_INFORMATIONAL,
                            ]);
                            $act->save();

                            $ticket->client_state = '"Bootup successful" message was not recieved, but client is successfully booted up. Client is now unlocked.';
                            $ticket->save(false);

                            $this->logInfo(substitute('Unlocking ticket {token} successful.', [
                                'token' => $ticket->token,
                            ]), true, true, true);
                        } else {

                            $act = new Activity([
                                    'ticket_id' => $ticket->id,
                                    'description' => yiit('activity', '"Bootup successful" message was not recieved, but client is successfully booted up. Client cannot be unlocked. Error: {error}'),
                                    'description_params' => [ 'error' => json_encode($ticket->getErrors()) ],
                                    'severity' => Activity::SEVERITY_CRITICAL,
                            ]);
                            $act->save();

                            $this->logInfo(substitute('Unlocking ticket {token} failed, but command was successful, error: {error}', [
                                'token' => $ticket->token,
                                'error' => json_encode($ticket->getErrors()),
                            ]), true, true, true);

                        }

                    }
                }
                $this->unlockItem($ticket);
            }
        }

        $this->logInfo('Ticket unlock done.', true, false, true);
        $this->unlocked = microtime(true);
    }

    /**
     * @inheritdoc
     */
    public function lockItem ($ticket)
    {
        return $this->lock($ticket->id . "_unlock");
    }

    /**
     * @inheritdoc
     */
    public function unlockItem ($ticket)
    {
        return $this->unlock($ticket->id . "_unlock");
    }

    /**
     * @inheritdoc
     */
    public function getNextItem ()
    {
        if ($this->unlocked < microtime(true) - $this->unlockInterval){
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function stop($cause = null)
    {
        parent::stop($cause);
    }

}
