<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\Activity;
use app\models\Ticket;
use app\models\Screenshot;
use yii\helpers\FileHelper;

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
    public $inc_emptydirs = false;
    public $path;
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
            [['path'], 'default', 'value' => '/', 'on' => self::SCENARIO_GENERATE],
            [['path'], 'validatePath', 'on' => self::SCENARIO_GENERATE],
            [['inc_dotfiles', 'inc_screenshots', 'inc_emptydirs'], 'boolean', 'on' => self::SCENARIO_GENERATE],
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
            'inc_dotfiles' => \Yii::t('results', 'Include hidden files (dot-files)'),
            'inc_screenshots' => \Yii::t('results', 'Include Screenshots'),
            'inc_pattern' => \Yii::t('results', 'Include only files of type (will include all files if nothing is selected)'),
            'inc_ids' => \Yii::t('results', 'Tickets'),
            'inc_emptydirs' => \Yii::t('results', 'Include empty directories'),
            'path' => \Yii::t('results', 'Path'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'path' => \Yii::t('results', 'This specifies the <b>path in the backup to include</b>. In most cases this may not be filled out, thus leave it empty to include all files.<br>If all students placed their result in a specific directory - say <code>Desktop/Hand-in</code> relative to the <i>Remote Backup Path</i> - you can provide this path here, to just include the relevant parts of the result.'),
            'inc_dotfiles' => \Yii::t('results', 'If set, files with names starting with a dot (dot-files, Ex. <code>.bashrc</code>) will be included in the generated result. These files are mostly related to the <b>system configuration or user profile settings</b>. In most cases this is not needed, unless the student itself creates dot-files which are part of his exam result. Notice that, if enabled, this can massively increase the size if the resulting ZIP-file.'),
            'inc_screenshots' => \Yii::t('results', 'If <i>Screenshots</i> are enabled (see exam configuration), this will <b>include all screenshots</b> taken in a separate directory to the exam result. Notice screenshots can also be viewed in the ticket view under "Screenshots".'),
            'inc_pattern' => \Yii::t('results', 'This is to <b>include only several types of files</b> to the exam result. The file name is then tested against the endings listed underneath. Multiple items can be selected. If no item is selected, all types of files will be included (except hidden files, if set).'),
            'inc_ids' => \Yii::t('results', 'Select a list of <b>tickets to include</b> in the result file. The more tickets selected, the bigger the size of the ZIP-file. By default, all closed or submitted tickets with no result handed back are preselected.'),
            'inc_emptydirs' => \Yii::t('results', 'Directories are included, even if they are empty.'),
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
            $comment .= \Yii::t('results', 'Generated at') . ': ' . date('c') . PHP_EOL;
            $comment .= \Yii::t('results', 'Options') . ':' . PHP_EOL;
            $comment .= '  ' . \Yii::t('results', 'Path') . ': ' . $this->path . PHP_EOL;
            $comment .= '  ' . \Yii::t('results', 'Include dotfiles') . ': ' . ($this->inc_dotfiles ? 'true' : 'false') . PHP_EOL;
            $comment .= '  ' . \Yii::t('results', 'Include screenshots') . ': ' . ($this->inc_screenshots ? 'true' : 'false') . PHP_EOL;
            $comment .= '  ' . \Yii::t('results', 'Include empty directories') . ': ' . ($this->inc_emptydirs ? 'true' : 'false') . PHP_EOL;
            $comment .= '  ' . \Yii::t('results', 'Include file types') . ': ' . (is_array($this->inc_pattern) ? implode(', ', $this->inc_pattern) : '') . PHP_EOL;
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
                            if ($file->isDir() &&  boolval($this->inc_emptydirs) === false) {
                                continue;
                            }

                            $file = realpath($file);

                            // exclude rdiff-backup-data directory
                            if (strpos($file, realpath($origSource . '/rdiff-backup-data')) === 0) { continue; }

                            // exclude Screenshots if set
                            $screenshotsDir = Screenshot::getScreenshotDir($ticket->token);
                            if (strpos($file, realpath($origSource . '/' . $screenshotsDir)) === 0) {
                                if (boolval($this->inc_screenshots)) {
                                    $this->zipInclude($file, $ticket->name . '/' . str_replace($origSource . '/', '', $file), $zip);
                                    continue;
                                } else {
                                    continue;
                                }
                            }

                            // exclude dotfiles if set
                            if (!boolval($this->inc_dotfiles)) {
                                if (strpos($file, '/.') !== false) { continue; }
                            }

                            if (strpos($file, realpath($source)) !== 0) { continue; }

                            // validate against file endings
                            if (!empty($this->inc_pattern) && !is_dir($file)) {
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

                            // if the file is readable (permission set) (overlayFS whiteouts will have no read permission set)
                            if (!is_readable($file)) { continue; }

                            // if all tests passed, include the file/dir
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
        $target = $this->sanitizePath($target);
        if (is_dir($source) === false) {
            return $zip->addFile($source, $target);
        } else {
            return $zip->addEmptyDir($target);
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
     * Sanitize path for Windows and Mac systems
     *
     * @return string filename
     * @see https://stackoverflow.com/a/42058764/2768341
     * @todo also transliterate characters (see http://userguide.icu-project.org/transforms/general)
     */
    public function sanitizePath($filename, $replacement = '')
    {
        $filename = preg_replace(
            '~
            [\<\>\:\"\\\|\?\*]            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
            ~x',
            $replacement, $filename);

        return $filename;
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

            $dir = $this->dirs[$ticket->token];

            $zipFile = \Yii::$app->params['resultPath'] . '/' . $ticket->token . '.zip';
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

            if (file_exists($zipFile)) {
                $ticket->result = $zipFile;
                $ticket->save();

                $act = new Activity([
                    'ticket_id' => $ticket->id,
                    'description' => yiit('activity', 'Exam result handed in.'),
                    'severity' => Activity::SEVERITY_INFORMATIONAL,
                ]);
                $act->save();
            }

        }

        FileHelper::removeDirectory($tmp);

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
     * Generates an error message when the path is invalid
     *
     * @param string $attribute - the attribute
     * @param array $params
     * @return void
     */
    public function validatePath($attribute, $params, $validator)
    {
        $path = FileHelper::normalizePath($this->exam->backup_path . '/' . $this->$attribute);
        $backup_path = FileHelper::normalizePath($this->exam->backup_path);
        if (strpos($path, $backup_path) !== 0) {
            $this->addError($attribute, \Yii::t('results', 'This path is invalid. You can only include files within the "Remote Backup Path".'));
        }
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
