<?php

namespace app\models;

use Yii;
use app\models\Base;
use yii\helpers\Html;
use app\components\HistoryBehavior;

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
class Exam extends Base
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
                $this->md5 = $this->file == null ? null : @md5_file($this->file);
                $this->{"file_analyzed"} = 0;
            }
        });

        # default values
        $this->backup_path = $this->isNewRecord ? '/home/user' : $this->backup_path;
        $this->screenshots_interval = $this->isNewRecord ? 5 : $this->screenshots_interval;
        $this->libre_autosave_interval = $this->isNewRecord ? 10 : $this->libre_autosave_interval;
        $this->libre_autosave_path = $this->isNewRecord ? '/home/user/.config/libreoffice/4/user/tmp' : $this->libre_autosave_path;
        $this->libre_createbackup_path = $this->isNewRecord ? '/home/user/.config/libreoffice/4/user/backup' : $this->libre_createbackup_path;
        $this->max_brightness = $this->isNewRecord ? 100 : $this->max_brightness;
    }

    /**
     * @inheritdoc 
     */
    public function behaviors()
    {
        return [
            'HistoryBehavior' => [
                'class' => HistoryBehavior::className(),
                'attributes' => [
                    'name' => 'text',
                    'subject' => 'text',
                    'file' => 'text',
                    'file2' => 'text',
                    'grp_netdev' => 'boolean',
                    'allow_sudo' => 'boolean',
                    'allow_mount' => 'boolean',
                    'firewall_off' => 'boolean',
                    'screenshots' => 'boolean',
                    'url_whitelist' => 'ntext',
                    'backup_path' => 'text',
                    'time_limit' => 'text',
                    'screenshots_interval' => 'text',
                    'libre_autosave' => 'boolean',
                    'libre_autosave_interval' => 'text',
                    'libre_autosave_path' => 'text',
                    'libre_createbackup' => 'boolean',
                    'libre_createbackup_path' => 'text',
                    'max_brightness' => 'text',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc 
     */
    public function rules()
    {
        return [
            [['id', 'name', 'subject', 'examFile', 'user_id', 'grp_netdev', 'allow_sudo', 'allow_mount', 'firewall_off', 'screenshots', 'screenshots_interval', 'url_whitelist', 'time_limit', 'libre_autosave', 'libre_autosave_interval', 'libre_autosave_path', 'libre_createbackup', 'libre_createbackup_path', 'max_brightness'], 'validateRunningTickets'],
            [['name', 'subject', 'backup_path', 'screenshots_interval', 'libre_autosave_interval', 'libre_autosave_path', 'libre_createbackup_path'], 'required'],
            [['time_limit'], 'integer', 'min' => 0],
            [['screenshots_interval', 'libre_autosave_interval'], 'integer', 'min' => 1],
            [['user_id'], 'integer'],
            [['name', 'subject'], 'string', 'max' => 52],
            [['max_brightness'], 'integer', 'min' => 0, 'max' => 100],
            [['file', 'file2'], 'file', 'skipOnEmpty' => true, 'extensions' => ['squashfs', 'zip'], 'checkExtensionByMimeType' => false],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('exams', 'ID'),
            'createdAt' => \Yii::t('exams', 'Created At'),
            'name' => \Yii::t('exams', 'Name'),
            'subject' => \Yii::t('exams', 'Subject'),
            'file' => \Yii::t('exams', 'Exam Image File (squashfs)'),
            'fileInfo' => \Yii::t('exams', 'Squashfs File Info'),
            'fileSize' => \Yii::t('exams', 'Squashfs File Size'),
            'file2' => \Yii::t('exams', 'Exam Zip File'),
            'file2Info' => \Yii::t('exams', 'Zip File Info'),
            'file2Size' => \Yii::t('exams', 'Zip File Size'),
            'md5' => \Yii::t('exams', 'MD5 Checksum'),
            'file_list' => \Yii::t('exams', 'File List'),
            'user_id' => \Yii::t('exams', 'User ID'),
            'grp_netdev' => \Yii::t('exams', 'User can edit network connections'),
            'allow_sudo' => \Yii::t('exams', 'User can gain root privileges by sudo'),
            'allow_mount' => \Yii::t('exams', 'User has access to external filesystems such as USB Sticks'),
            'firewall_off' => \Yii::t('exams', 'Disable Firewall'),
            'screenshots' => \Yii::t('exams', 'Take Screenshots'),
            'url_whitelist' => \Yii::t('exams', 'HTTP URL Whitelist'),
            'time_limit' => \Yii::t('exams', 'Time Limit'),
            'backup_path' => \Yii::t('exams', 'Remote Backup Path'),
            'ticketInfo' => \Yii::t('exams', 'Related Tickets'),
            'ticketCount' => \Yii::t('exams', '# Tickets'),
            'openTicketCount' => \Yii::t('exams', 'Open Tickets'),
            'runningTicketCount' => \Yii::t('exams', 'Running Tickets'),
            'closedTicketCount' => \Yii::t('exams', 'Closed Tickets'),
            'submittedTicketCount' => \Yii::t('exams', 'Submitted Tickets'),
            'libre_autosave' => \Yii::t('exams', 'Libreoffice: Save AutoRecovery information'),
            'libre_autosave_interval' => \Yii::t('exams', 'Libreoffice: Save AutoRecovery information interval'),
            'libre_autosave_path' => \Yii::t('exams', 'Libreoffice: Save AutoRecovery information path'),
            'libre_createbackup' => \Yii::t('exams', 'Libreoffice: Always create backup copy'),
            'libre_createbackup_path' => \Yii::t('exams', 'Libreoffice: Always create backup copy path'),
            'max_brightness' => \Yii::t('exams', 'Maximum brightness')
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'name' => \Yii::t('exams', 'The name of the exam. This value may not be unique, but it should be used to <b>identify the exam</b>.'),
            'subject' => \Yii::t('exams', 'The school subject.'),
            'time_limit' => \Yii::t('exams', 'If this value (in minutes) is set, the exam status view of the student will show the time left. This has <b>NO indication</b> elsewhere. It is just of informative purpose. Set to <code>0</code> or leave empty for no time limit.'),
            'grp_netdev' => \Yii::t('exams', 'If set, the exam student will be in the network group <code>netdev</code>. Members of this group can manage network interfaces through the network manager and wicd. Notice, that the student will be able to leave the exam network. <b>This should not be set, unless you know what you are doing.</b>'),
            'allow_sudo' => \Yii::t('exams', 'If set, the exam student will be able to switch to the root user <b>without password</b>. <b>This should not be set, unless you know what you are doing.</b>'),
            'allow_mount' => \Yii::t('exams', 'If set, the exam student will be able to mount external filesystems such as USB Sticks, Harddrives or Smartcard. <b>This should not be set, unless you know what you are doing.</b>'),
            'firewall_off' => \Yii::t('exams', 'If set, disables the Firewall and access to all network resourses is given. <b>This should not be set, unless you know what you are doing.</b>'),
            'screenshots' => \Yii::t('exams', 'If set, the system will <b>create screenshots every n minutes</b>. The Interval can be set in minutes. Those screenshots will appear in the Ticket view under the register "Screenshots". When generating exam results, they can also be included.'),
            'url_whitelist' => \Yii::t('exams', 'URLs given in this list will be allowed to visit by the exam student during the exam. Notice, due to this date, only URLs starting with <code>http://</code> are supported, therefore https://</code> URLs will be ignored. The URLs should be provided newline separated. The provided URLs are allowed even if the Firewall is enabled.'),
            'backup_path' => \Yii::t('exams', 'Specifies the <b>directory to backup</b> at the target machine. This should be an absolute path. Mostly this is set to <code>/home/user</code>, which is the home directory of the user under which the exam is taken. The exam server will then backup the ALL files in <code>/home/user</code> that have changed since the exam started. For more information please visit <code>Manual / Remote Backup Path</code>'),
            'file' => \Yii::t('exams', 'Use a <b>squashfs-Filesystem or a ZIP-File</b> for the exam. Squashfs is a highly compressed read-only filesystem for Linux. This file contains all files, settings and applications for the exam (all changes made on the original machine). These changes are applied to the exam system as soon as the exam starts. See <b>Help</b> for more information on how to create those files.'),
            'libre_createbackup' => \Yii::t('exams', 'If the <b>Always create backup copy</b> option is selected, the old version of the file is saved to the backup directory whenever you save the current version of the file. The backup copy has the same name as the document, but the extension is <code>.BAK</code>. If the backup folder (default location: <code>/home/user/.config/libreoffice/4/backup</code>) already contains such a file, it will be overwritten without warning. (See <a target="_blank" href="https://help.libreoffice.org/Common/Saving_Documents_Automatically">LibreOffice Help</a>)'),
            'libre_autosave' => \Yii::t('exams', 'Check to <b>save recovery information automatically every <code>n</code> minutes</b>. This command saves the information necessary to restore the current document in case of a crash (default location: <code>/home/user/.config/libreoffice/4/tmp</code>). Additionally, in case of a crash LibreOffice tries automatically to save AutoRecovery information for all open documents, if possible. (See <a target="_blank" href="https://help.libreoffice.org/Common/Saving_Documents_Automatically">LibreOffice Help</a>)'),
            'max_brightness' => \Yii::t('exams', 'Maximum screen brightness in percent. Notice that some devices have buttons to adjust screen brightness on hardware level. This cannot be controlled by this setting.'),
            'ticketInfo' => \Yii::t('exams', 'Related Tickets (# open, # running, # closed, # submitted)/# total tickets')
        ];
    }

    /**
     * @return boolean
     */
    public function upload($file)
    {
        $this->filePath = \Yii::$app->params['uploadPath'] . '/' . generate_uuid() . '.' . $file->extension;

        if ($this->validate(['file'])) {
            return $file->saveAs($this->filePath, true);
        } else {
            return false;
        }
    }

    /**
     * @return boolean
     */
    public function deleteFile($type = 'squashfs')
    {
        if ($type == 'squashfs') {
            $file = $this->file;
            $this->file = null;
            $this->md5 = null;
            $this->{"file_analyzed"} = 0;
        } else if ($type == 'zip') {
            $file = $this->file2;
            $this->file2 = null;
        }

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
        $this->deleteFile('squashfs');
        $this->deleteFile('zip');
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
     * @return \yii\db\ActiveQuery
     */
    public function getScreen_capture()
    {
        return $this->getScreenCapture();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getScreenCapture()
    {
        return $this->hasOne(ScreenCapture::className(), ['id' => 'screen_capture_id']);
    }

    /* Getter for user name */
    public function getUserName()
    {
        return $this->user->username;
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
     * Returns true whether both files are consistent and not empty
     *
     * @return boolean
     */
    public function getFileConsistency()
    {
        if (empty($this->file) && empty($this->file2)) {
            return false;
        } else if (empty($this->file)) {
            return $this->file2Consistency;
        } else if (empty($this->file2)) {
            return $this->file1Consistency;
        } else {
            return $this->file1Consistency && $this->file2Consistency;
        }
    }

    /**
     * @return boolean
     */
    public function getFile1Consistency()
    {
        if (empty($this->file)) {
            return false;
        } else if (!Yii::$app->file->set($this->file)->exists) {
            return false;
        } else {
            return strpos($this->fileInfo, 'Squashfs') !== false ? true : false;
        }
    }

    /**
     * @return string
     */
    public function getFile2Size()
    {
        return Yii::$app->file->set($this->file2)->size;
    }

    /**
     * @return string
     */
    public function getFile2Info()
    {
        return str_replace(',', '<br>', Yii::$app->file->set($this->file2)->info);
    }

    /**
     * @return boolean
     */
    public function getFile2Consistency()
    {
        if (empty($this->file2)) {
            return false;
        } else if (!Yii::$app->file->set($this->file2)->exists) {
            return false;
        } else {
            return strpos($this->file2Info, 'Zip') !== false ? true : false;
        }
    }

    /**
     * @return string
     */
    public function getTicketInfo()
    {
        $a = array();

        $this->openTicketCount != 0 ?
            $a[] = Html::a($this->openTicketCount, [
                'ticket/index',
                'TicketSearch[examId]' => $this->id,
                'TicketSearch[state]' => 0
            ], [
                'data-pjax' => 0,
                'class' => 'bg-success text-success',
                'title' => \Yii::t('exams', 'Number of Tickets in open state')
            ]) : null;
        $this->runningTicketCount != 0 ?
            $a[] = Html::a($this->runningTicketCount, [
                'ticket/index',
                'TicketSearch[examId]' => $this->id,
                'TicketSearch[state]' => 1
            ], [
                'data-pjax' => 0,
                'class' => 'bg-info text-info',
                'title' => \Yii::t('exams', 'Number of Tickets in running state')
            ]) : null;
        $this->closedTicketCount != 0 ?
            $a[] = Html::a($this->closedTicketCount, [
                'ticket/index',
                'TicketSearch[examId]' => $this->id,
                'TicketSearch[state]' => 2
            ], [
                'data-pjax' => 0,
                'class' => 'bg-danger text-danger',
                'title' => \Yii::t('exams', 'Number of Tickets in closed state')
            ]) : null;
        $this->submittedTicketCount != 0 ? 
            $a[] = Html::a($this->submittedTicketCount, [
                'ticket/index',
                'TicketSearch[examId]' => $this->id,
                'TicketSearch[state]' => 3
            ], [
                'data-pjax' => 0,
                'class' => 'bg-warning text-warning',
                'title' => \Yii::t('exams', 'Number of Tickets in submitted state')
            ]) : null;

        return ( count($a) == 0 ? 
                '' : 
                ( count($a) == 1 ? 
                    implode(',', $a) . '/' : 
                    ( '(' . implode(',', $a) . ')/' )
                )
            ) . 
            ( $this->ticketCount != 0 ? 
                Html::a($this->ticketCount, [
                    'ticket/index',
                    'TicketSearch[examId]' => $this->id,
                ], [
                    'data-pjax' => 0,
                    'class' => 'text-muted',
                    'title' => \Yii::t('exams', 'Total number of Tickets')
                ]) : 
                $this->ticketCount );
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
        $this->runningTicketCount != 0 ? $this->addError($attribute, \Yii::t('exams', 'Exam edit is disabled while there {n,plural,=1{is one ticket} other{are # tickets}} in "Running" state.', [
            'n' => $this->runningTicketCount
        ])) : null;
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
