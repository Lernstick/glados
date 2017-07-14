<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\Activity;
use app\models\Ticket;

/**
 * This is the model class for the result directory.
 *
 */
class Result extends Model
{

    /**
     * @var UploadedFile
     */
    public $resultFile;
    public $filePath;
    public $file;
    public $hash;
    public $exam_id;

    /* generation options */
    public $inc_dotfiles = false;
    public $inc_screenshots = true;
    public $path = '/';
    public $inc_pattern = [];
    public $inc_ids = [];

    private $_tickets = [];
    private $_tokens = [];
    private $_dirs = [];
    private $_exam;
    private $_types = [
        'word' => '/.*\.odt$|.*\.doc$|.*\.docx$|.*\.rtf$/i',
        'excel' => '/.*\.xl.*|.*\.ods$/i',
        'pp' => '/.*\.ppt$|.*\.pptm$|.*\.pps$|.*\.ppsx$|.*\.ppsm$|.*\.potx$|.*\.pot$|.*\.potm$|.*\.odp$/i',
        'text' => '/.*\.txt$|.*\.cond$|.*\.cfg$|.*\.ini$/i',
        'pdf' => '/.*\.pdf$/i',
        'images' => '/.*\.jpg$|.*\.png$|.*\.gif$|.*\.jpeg$/i',
    ];

    /* scenario constants */
    const SCENARIO_GENERATE = 'generate';
    const SCENARIO_SUBMIT = 'submit';

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'zip', 'checkExtensionByMimeType' => true, 'on' => self::SCENARIO_SUBMIT],        
            [['exam_id'], 'required', 'on' => self::SCENARIO_GENERATE],                    
            [['path'], 'required', 'on' => self::SCENARIO_GENERATE],
            [['inc_dotfiles', 'inc_screenshots'], 'boolean', 'on' => self::SCENARIO_GENERATE],
            [['inc_pattern'], 'each', 'rule' => ['string'], 'on' => self::SCENARIO_GENERATE],
            [['inc_ids'], 'each', 'rule' => ['integer'], 'on' => self::SCENARIO_GENERATE],
            [['inc_ids'], 'required', 'on' => self::SCENARIO_GENERATE],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'inc_dotfiles' => 'Include hidden files (dot-files)',
            'inc_screenshots' => 'Include screenshots',
            'inc_pattern' => 'Include only files of type (will include all files if nothing is selected)',
            'inc_ids' => 'Tickets',
            'path' => 'Path',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExam()
    {
        if (is_object($this->_exam)) {
            return $this->_exam;
        } else {
            return Exam::findOne(['id' => $this->exam_id]);
        }
    }

    /**
     * Generates a ZIP File from all closed or submitted Tickets.
     * 
     * @return null|false|string    null: if there is no closed or submitted ticket
     *                              false: if an error occurred during generation
     *                              string: the zip File path
     */
    public function generateZip()
    {

        #$tickets = Ticket::find()->where([ 'and', ['exam_id' => $this->exam->id], [ 'not', [ "start" => null ] ], [ 'not', [ "end" => null ] ] ])->all();
        $tickets = Ticket::findAll($this->inc_ids);

        if(!$tickets){
            return null;
        } else {

            $zip = new \ZipArchive;
            $zipFile = tempnam(sys_get_temp_dir(), 'ZIP');
            $res = $zip->open($zipFile, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);

            $comment = $this->exam->name . ' - ' . $this->exam->subject . PHP_EOL . PHP_EOL;
            $comment .= 'Generated at: ' . date('c') . PHP_EOL;
            $comment .= 'Options:' . PHP_EOL;
            $comment .= '  Path: ' . $this->path . PHP_EOL;
            $comment .= '  Include dotfiles: ' . ($this->inc_dotfiles ? 'true' : 'false') . PHP_EOL;
            $comment .= '  Include screenshots: ' . ($this->inc_screenshots ? 'true' : 'false') . PHP_EOL;
            $comment .= '  Include file types: ' . (is_array($this->inc_pattern) ? implode(', ', $this->inc_pattern) : '') . PHP_EOL;
            $comment .= PHP_EOL;

            if ($res === TRUE) {

                foreach($tickets as $ticket) {
                    $options = array('add_path' => $ticket->name . '/', 'remove_all_path' => TRUE);

                    $zip->addEmptyDir($ticket->name);
                    $comment .= $ticket->token . ': ' . ($ticket->test_taker ? $ticket->test_taker : '(not set)') . PHP_EOL;

                    $origSource = realpath(\Yii::$app->params['backupPath'] . '/' . $ticket->token);
                    $source = realpath($origSource . '/' . $this->path . '/');
                    if (is_dir($origSource)) {
                        $files = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator(
                                $origSource,
                                \FilesystemIterator::SKIP_DOTS
                            ),
                            \RecursiveIteratorIterator::SELF_FIRST
                        );

                        foreach ($files as $file) {
                            if ($file->isDir()) continue;
                            $file = realpath($file);

                            // exclude rdiff-backup-data directory
                            if (strpos($file, realpath($origSource . '/rdiff-backup-data')) === 0) { continue; }

                            // exclude dotfiles if set
                            if (!boolval($this->inc_dotfiles)) {
                                if (strpos($file, '/.') !== false) { continue; }
                            }

                            // exclude Screenshots if set
                            if (strpos($file, realpath($origSource . '/Screenshots')) === 0) {
                                if (boolval($this->inc_screenshots)) {
                                    $this->zipInclude($file, $ticket->name . '/' . str_replace($origSource . '/', '', $file), $zip);
                                    continue;
                                } else {
                                    continue;
                                }
                            }

                            if (strpos($file, realpath($source)) !== 0) { continue; }

                            if (!empty($this->inc_pattern)) {
                                foreach ($this->inc_pattern as $pattern) {
                                    if (isset($this->_types[$pattern])) {
                                        $p = $this->_types[$pattern];
                                        $bn = basename($file);
                                        if (preg_match($p, $bn) === 1) {
                                            $this->zipInclude($file, $ticket->name . '/' . str_replace($source . '/', '', $file), $zip);
                                            continue;
                                        }
                                    }
                                }
                                continue;
                            }

                            $this->zipInclude($file, $ticket->name . '/' . str_replace($source . '/', '', $file), $zip);
                        }
                    }
                }
                $zip->setArchiveComment($comment);
                $zip->close();
                return $zipFile;
            } else {
                @unlink($zipFile);
                return false;
            }
        }
    }

    /**
     * @return boolean
     */
    private function zipInclude ($source, $target, $zip)
    {
        if (is_dir($source) === false) {
            return $zip->addFile($source, $target);
        }
    }

    /**
     * @return boolean
     */
    public function upload()
    {
        $this->filePath = \Yii::$app->params['resultPath'] . '/' . generate_uuid() . '.' . $this->file->extension;

        if ($this->validate(['file'])) {
            return $this->file->saveAs($this->filePath, true);
        } else {
            return false;
        }
    }

    /**
     * @return boolean
     */
    public function submit()
    {
        $zip = new \ZipArchive(); 
        $tmp = tempnam(sys_get_temp_dir(), '');
        if (file_exists($tmp)) { unlink($tmp); }
        mkdir($tmp);

        if ($zip->open($this->file) === TRUE) {
            $zip->extractTo($tmp);
            $zip->close();
        }

        foreach($this->tickets as $ticket) {
            $ticket->result = null;
            $ticket->save();
        }

        foreach($this->dirs as $token => $dir) {

            $zipFile = \Yii::$app->params['resultPath'] . '/' . $token . '.zip';
            $source = $tmp . '/' . $dir;

            $tzip = new \ZipArchive;
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }
            $res = $tzip->open($zipFile, \ZIPARCHIVE::CREATE);

            if ($res === TRUE) {

                if (is_dir($source)) {
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator(
                            $source,
                            \FilesystemIterator::SKIP_DOTS
                        ),
                        \RecursiveIteratorIterator::SELF_FIRST
                    );
                    foreach ($files as $file) {
                        $file = realpath($file);

                        if (is_dir($file) === true) {
                            $tzip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                        }else if (is_file($file) === true) {
                            $tzip->addFile($file, str_replace($source . '/', '', $file));
                        }
                    }
                }
                $tzip->close();
            } else {
                @unlink($zipFile);
            }
        }

        foreach($this->tickets as $ticket) {
            $result = realpath(\Yii::$app->params['resultPath'] . '/' . $ticket->token . '.zip');
            if (file_exists($result)) {
                $ticket->result = $result;
                $ticket->save();

                $act = new Activity([
                    'ticket_id' => $ticket->id,
                    'description' => 'Exam result handed in.'
                ]);
                $act->save();
            }
        }

        return null;
    }


    public function getTokens()
    {
        if (empty($this->_tickets)) {
            $this->getTickets();
        }
        return $this->_tokens;
    }

    public function getDirs()
    {
        if (empty($this->_dirs)) {
            $this->getTickets();
        }
        return $this->_dirs;
    }

    public function getTickets()
    {
        if (empty($this->_tickets)) {
            $zip = new \ZipArchive(); 
            $regex = '/^(?<name>.+) - (?<token>[A-Fa-f0-9]+)\/$/';            

            if ($zip->open($this->file) === TRUE) {
                for($i=0; $i < $zip->numFiles; $i++){ 
                    $stat = $zip->statIndex($i);
                    $matches = null;
                    if (preg_match($regex, $stat['name'], $matches) === 1) {
                        $this->_tokens[] = $matches['token'];
                        $this->_dirs[$matches['token']] = substr($matches[0], 0, -1);
                    }
                }
                $this->_tickets = Ticket::find()->where(['token' => $this->_tokens])->all();
            }
        }
        return $this->_tickets;
    }

    /**
     * Return the Result model related to the hash
     *
     * @param string $hash - hash
     * @return Result
     */
    public function findOne($hash)
    {
        $file = \Yii::$app->params['resultPath'] . '/' . $hash;

        if(Yii::$app->file->set($file)->exists === false){
            return null;
        }

        return new Result([
            'file' => $file,
            'hash' => basename($file),
        ]);
    }

}
