<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "exam".
 *
 * @property integer $id
 * @property string $name
 * @property string $subject
 * @property boolean $grp_netdev
 * @property boolean $allow_sudo
 * @property boolean $allow_mount
 * @property boolean $firewall_off
 * @property boolean $screenshots
 * @property string $file
 * @property integer $user_id
 * @property string $file_list
 *
 * @property User $user
 * 
 * @property Ticket[] $tickets
 * @property integer ticketCount
 * @property integer openTicketCount
 * @property integer runningTicketCount
 * @property integer closedTicketCount
 */
class Exam extends \yii\db\ActiveRecord
{

    /**
     * @var UploadedFile
     */
    public $examFile;
    public $filePath;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'exam';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $instance = $this;
        $this->on(self::EVENT_BEFORE_INSERT, function($instance){
            $this->user_id = \Yii::$app->user->id;
        });

        $this->on(self::EVENT_BEFORE_UPDATE, function($instance){
            if ($this->getOldAttribute('file') != $this->file) {
                $this->md5 = $this->file == null ? null : md5_file($this->file);
                $this->{"file_analyzed"} = 0;
            }
        });

        $this->backup_path = $this->isNewRecord ? '/home/user' : $this->backup_path;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'name', 'subject', 'examFile', 'user_id', 'grp_netdev', 'allow_sudo', 'allow_mount', 'firewall_off', 'screenshots', 'url_whitelist', 'time_limit'], 'validateRunningTickets'],
            [['name', 'subject', 'backup_path'], 'required'],
            [['time_limit'], 'integer', 'min' => 0],
            [['user_id'], 'integer'],
            [['name', 'subject'], 'string', 'max' => 52],
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'squashfs', 'checkExtensionByMimeType' => false],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'subject' => 'Subject',
            'file' => 'File',
            'md5' => 'MD5 Checksum',
            'file_list' => 'File List',
            'user_id' => 'User ID',
            'grp_netdev' => 'User can edit network connections',
            'allow_sudo' => 'User can gain root privileges by sudo',
            'allow_mount' => 'User has access to external filesystems such as USB Sticks',
            'firewall_off' => 'Disable Firewall',
            'screenshots' => 'Take Screenshots',
            'url_whitelist' => 'HTTP URL Whitelist',
            'time_limit' => 'Time Limit',
            'backup_path' => 'Remote Backup Path',
            'ticketCount' => 'Total Tickets',
            'openTicketCount' => 'Open Tickets',
            'runningTicketCount' => 'Running Tickets',
            'closedTicketCount' => 'Closed Tickets',
            'submittedTicketCount' => 'Submitted Tickets',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'name' => 'The name of the exam',
        ];
    }

    /**
     * @return boolean
     */
    public function upload()
    {
        $this->filePath = \Yii::$app->params['uploadPath'] . '/' . generate_uuid() . '.' . $this->file->extension;

        if ($this->validate(['file'])) {
            return $this->file->saveAs($this->filePath, true);
        } else {
            return false;
        }
    }

    /**
     * @return boolean
     */
    public function deleteFile()
    {
        $file = $this->file;
        $this->file = null;
        $this->md5 = null;
        $this->{"file_analyzed"} = 0;

        if (file_exists($file) && is_file($file) && $this->save()) {
            return @unlink($file);
        }else{
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $this->deleteFile();
        return parent::delete();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return int
     */
    public function getTicketCount()
    {
        return intval($this->getTickets()->count());
    }

    /**
     * @return int
     */
    public function getOpenTicketCount()
    {
        return intval($this->getTickets()->where(["NULLIF(start, '')" => null, "NULLIF(end, '')" => null ])->count());
    }

    /**
     * @return int
     */
    public function getRunningTicketCount()
    {
        return intval($this->getTickets()->where([ 'and', [ 'not', [ "NULLIF(start, '')" => null ] ], [ "NULLIF(end, '')" => null ] ])->count());
    }

    /**
     * @return int
     */
    public function getClosedTicketCount()
    {
        return intval($this->getTickets()->where([ 'and', [ 'not', [ "NULLIF(start, '')" => null ] ], [ 'not', [ "NULLIF(end, '')" => null ] ], [ "NULLIF(test_taker, '')" => null ] ])->count());

    }

    /**
     * @return int
     */
    public function getSubmittedTicketCount()
    {
        return intval($this->getTickets()->where([ 'and', [ 'not', [ "NULLIF(start, '')" => null ] ], [ 'not', [ "NULLIF(end, '')" => null ] ], [ 'not', [ "NULLIF(test_taker, '')" => null ] ] ])->count());

    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTickets()
    {
        return $this->hasMany(Ticket::className(), ['exam_id' => 'id']);
    }

    /**
     * @return string
     */
    public function getFileSize()
    {
        return Yii::$app->file->set($this->file)->size;
    }

    /**
     * @return string
     */
    public function getFileInfo()
    {
        return str_replace(',', '<br>', Yii::$app->file->set($this->file)->info);
    }

    /**
     * @return boolean
     */
    public function getFileConsistency()
    {
        return (strpos($this->fileInfo, 'Squashfs') === false ? false : true);
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        //var_dump(Yii::$app->squashfs->set($this->file));die();
        $x = '';
        //$x .= 'root passwoord ' . (Yii::$app->squashfs->set($this->file)->file_exists('/etc/passwd') ? 'not ' : '') . 'set.';
        return $x;
    }

    public function validateRunningTickets($attribute, $params)
    {
        $this->runningTicketCount != 0 ? $this->addError($attribute, 'Exam update is disabled while there are ' . $this->runningTicketCount . ' tickets in "Running" state.') : null;
    }

    /**
     * @inheritdoc
     * @return ExamQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ExamQuery(get_called_class());
    }


}
