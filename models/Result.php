<?php

namespace app\models;

use Yii;
use yii\base\Model;

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

    private $_tickets = [];
    private $_tokens = [];
    private $_dirs = [];

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'zip', 'checkExtensionByMimeType' => true],        
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [];
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

        foreach($this->dirs as $token => $dir) {

            $zipFile = \Yii::$app->params['resultPath'] . '/' . $token . '.zip';
            $source = $tmp . '/' . $dir;

            print_r($zipFile .  PHP_EOL);
            print_r($source .  PHP_EOL);
            $tzip = new \ZipArchive;
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
