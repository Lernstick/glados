<?php

namespace app\commands;

use yii;
use app\commands\DaemonController;
use app\models\Exam;
use yii\helpers\Console;
use app\components\ShellCommand;

/**
 * Analyzer Daemon
 * Desc: TODO
 */
class AnalyzeController extends DaemonController
{

    public $fileList = [
        '/etc/lernstick-firewall/url_whitelist',
    ];

    public $return = false;
    public $exam = null;

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
    public function doJob($id = '')
    {
        while (true) {
            $this->exam = null;

            if ($id != ''){
                $this->exam =  Exam::findOne(['id' => $id]);
                $this->return = true;
            } else {
                $this->exam = $this->getNextExam();
            }

            if($this->exam  !== null) {
                $tmpdir = $this->unpack();
                if (file_exists($tmpdir . "/" . $this->fileList[0])) {
                    $this->exam->{"sq_url_whitelist"} = file_get_contents($tmpdir . "/" . $this->fileList[0]);
                }

                $this->exam->{"file_analyzed"} = 1;
                $this->exam->save(false);

                if (!empty($this->exam->md5)) {
                    $this->removeDirectory("/tmp/" . $this->exam->md5);
                }
            }

            if ($this->return == true) {
                return;
            }

            pcntl_signal_dispatch();
            sleep(rand(5, 10));
        }

    }

    private function unpack ()
    {
        chdir(dirname(__FILE__));
        if(file_exists("/tmp/" . $this->exam->md5) && !empty($this->exam->md5)) {
            $this->removeDirectory("/tmp/" . $this->exam->md5);
        }

        $cmd = "unsquashfs -d /tmp/" . escapeshellarg($this->exam->md5) . " " . escapeshellarg($this->exam->file)
             . " " . implode(" ", $this->fileList);
        echo $cmd . PHP_EOL;
        $cmd = new ShellCommand($cmd);

        $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use (&$output) {
            echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
        });

        $retval = $cmd->run();
        return "/tmp/" . $this->exam->md5;
    }

    private function removeDirectory ($path) {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
        return;
    }


    /**
     * Determines the next exam file to process
     *
     * @return Exam|null
     */
    private function getNextExam ()
    {

        $query = Exam::find()
            ->where(['not', ['file' => null]])
            ->andWhere(['file_analyzed' => 0]);


        // finally lock the next ticket and return it
        if (($exam = $query->one()) !== null) {
            return $exam;
        }

        return null;

    }


    /**
     * @inheritdoc
     */
    public function stop()
    {
        if ($this->exam !== null && !empty($this->exam->md5)) {
            $this->removeDirectory("/tmp/" . $this->exam->md5);
        }
        parent::stop();
    }

}
