<?php

namespace app\models;

use Yii;
use app\models\Base;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use app\models\file\ZipFile;
use app\components\HistoryBehavior;
use app\components\ElasticsearchBehavior;

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
 * @property string $file
 * @property integer $user_id
 * @property string $file_list
 *
 * @property User $user
 * 
 * @property Ticket[] $tickets
 * @property integer ticketCount
 * @property integer openTickets
 * @property integer runningTickets
 * @property integer closedTickets
 * @property integer submittedTickets
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
                    'backup_path' => 'text',
                    'time_limit' => 'text',
                ],
            ],
            'ElasticsearchBehavior' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => self::tableName(),
                // what the attributes mean
                'fields' => [
                    'createdAt', // this field has CURRENT_TIMESTAMP
                    'name',
                    'subject',
                    // calculated field
                    'fileInfo' => ['trigger_attributes' => ['file']],
                    'file2Info' => ['trigger_attributes' => ['file2']],
                    'user' => ['trigger_attributes' => ['user_id'], 'value_from' => 'user_id'],
                ],
                // mapping of elasticsearch
                'mappings' => [
                    'properties' => [
                        'createdAt'  => [
                            'type' => 'date',
                            'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis',
                        ],
                        'name'       => ['type' => 'text'],
                        'subject'    => ['type' => 'text'],
                        'fileInfo'   => ['type' => 'text'],
                        'file2Info'  => ['type' => 'text'],
                        'user'       => ['type' => 'integer'],
                    ],
                ],
            ],
            'ElasticsearchZip' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => 'file',
                'onlyIndexIf' => function($m) { return $m->zipFile->exists; },
                'fields' => [
                    'path' => function($m) { return $m->zipFile->path; },
                    'mimetype' => function($m) { return $m->zipFile->mimetype; },
                    'content' => function($m) { return $m->zipFile->toText; },
                    'size' => function($m) { return $m->zipFile->size; },
                    'exam' => function($m) { return $m->id; },
                    'user' => function($m) { return $m->user_id; },
                ],
                // mapping of elasticsearch
                'mappings' => [
                    'properties' => [
                        'path'     => ['type' => 'text'],
                        'mimetype' => ['type' => 'text'],
                        'content' =>  ['type' => 'text'],
                        'size'     => ['type' => 'integer'],
                        'exam'     => ['type' => 'integer'],
                        'user'     => ['type' => 'integer'],
                    ],
                ],
            ],
            'ExamZipContents' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => false, // look in FileInArchive model for fields and mappings
                'allModels' => [
                    'foreach' => function($class) { return ArrayHelper::getColumn(Exam::find()->all(), 'zipFile'); },
                    'allModels' => function($zipFile) { return $zipFile->files; },
                ],
                'onlyIndexIf' => function($exam) { return $exam->zipFile->exists; },
            ],
        ];
    }

    /**
     * @inheritdoc 
     */
    public function rules()
    {
        return [
            [['id', 'name', 'subject', 'examFile', 'user_id', 'grp_netdev', 'allow_sudo', 'allow_mount', 'firewall_off', 'time_limit'], 'validateRunningTickets'],
            [['name', 'subject', 'backup_path'], 'required'],
            [['time_limit'], 'integer', 'min' => 0],
            [['user_id'], 'integer'],
            [['name', 'subject'], 'string', 'max' => 52],
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
            'time_limit' => \Yii::t('exams', 'Time Limit'),
            'backup_path' => \Yii::t('exams', 'Remote Backup Path'),
            'ticketInfo' => \Yii::t('exams', 'Related Tickets'),
            'ticketCount' => \Yii::t('exams', '# Tickets'),
            'openTickets' => \Yii::t('exams', 'Open Tickets'),
            'runningTickets' => \Yii::t('exams', 'Running Tickets'),
            'closedTickets' => \Yii::t('exams', 'Closed Tickets'),
            'submittedTickets' => \Yii::t('exams', 'Submitted Tickets'),
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
            'backup_path' => \Yii::t('exams', 'Specifies the <b>directory to backup</b> at the target machine. This should be an absolute path. Mostly this is set to <code>/home/user</code>, which is the home directory of the user under which the exam is taken. The exam server will then backup the ALL files in <code>/home/user</code> that have changed since the exam started. For more information please visit <code>Manual / Remote Backup Path</code>'),
            'file' => \Yii::t('exams', 'Use a <b>squashfs-Filesystem or a ZIP-File</b> for the exam. Squashfs is a highly compressed read-only filesystem for Linux. This file contains all files, settings and applications for the exam (all changes made on the original machine). These changes are applied to the exam system as soon as the exam starts. See <b>Help</b> for more information on how to create those files.'),
            'ticketInfo' => \Yii::t('exams', 'Related Tickets (# open, # running, # closed, # submitted)/# total tickets')
        ];
    }

    /**
     * Returns the idle time in the monitor view
     * @return float idle time in seconds in the monitor view until the live image
     * is refreshed.
     */
    static public function monitor_idle_time()
    {
        return max(5.0, 3*\Yii::$app->params['monitorInterval']);
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
    public function getExam_setting()
    {
        return $this->hasMany(ExamSetting::className(), ['exam_id' => 'id'])
            ->with('belongsTo')
            ->with('detail')
            ->orderBy(['exam_setting.id' => SORT_DESC]);
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return array_combine(
            array_map(function($v){return $v->key;}, $this->exam_setting),
            array_map(function($v){return $v->jsonValue;}, $this->exam_setting)
        );
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
    public function getOpenTickets()
    {
        return intval($this->getTickets()->where([
            "NULLIF(start, '')" => null,
            "NULLIF(end, '')" => null
        ])->count());
    }

    /**
     * @return int
     */
    public function getRunningTickets()
    {
        return intval($this->getTickets()->where([
            'and',
            [ 'not', [ "NULLIF(start, '')" => null ] ],
            [ "NULLIF(end, '')" => null ]
        ])->count());
    }

    /**
     * @return int
     */
    public function getClosedTickets()
    {
        return intval($this->getTickets()->where([
            'and',
            [ 'not', [ "NULLIF(start, '')" => null ] ],
            [ 'not', [ "NULLIF(end, '')" => null ] ],
            [ "NULLIF(test_taker, '')" => null ]
        ])->count());
    }

    /**
     * @return int
     */
    public function getSubmittedTickets()
    {
        return intval($this->getTickets()->where([
            'and',
            [ 'not', [ "NULLIF(start, '')" => null ] ],
            [ 'not', [ "NULLIF(end, '')" => null ] ],
            [ 'not', [ "NULLIF(test_taker, '')" => null ] ]
        ])->count());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTickets()
    {
        return $this->hasMany(Ticket::className(), ['exam_id' => 'id']);
    }

    /**
     * @return ZipFile
     */
    public function getZipFile()
    {
        return new ZipFile([
            'path' => $this->file2,
            'relation' => $this,
        ]);
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
        if (!empty($this->file)) {
            return str_replace(',', '<br>', Yii::$app->file->set($this->file)->info);
        } else {
            return null;
        }
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

        $this->openTickets != 0 ?
            $a[] = Html::a($this->openTickets, [
                'ticket/index',
                'TicketSearch[examId]' => $this->id,
                'TicketSearch[state]' => 0
            ], [
                'data-pjax' => 0,
                'class' => 'bg-success text-success',
                'title' => \Yii::t('exams', 'Number of Tickets in open state')
            ]) : null;
        $this->runningTickets != 0 ?
            $a[] = Html::a($this->runningTickets, [
                'ticket/index',
                'TicketSearch[examId]' => $this->id,
                'TicketSearch[state]' => 1
            ], [
                'data-pjax' => 0,
                'class' => 'bg-info text-info',
                'title' => \Yii::t('exams', 'Number of Tickets in running state')
            ]) : null;
        $this->closedTickets != 0 ?
            $a[] = Html::a($this->closedTickets, [
                'ticket/index',
                'TicketSearch[examId]' => $this->id,
                'TicketSearch[state]' => 2
            ], [
                'data-pjax' => 0,
                'class' => 'bg-danger text-danger',
                'title' => \Yii::t('exams', 'Number of Tickets in closed state')
            ]) : null;
        $this->submittedTickets != 0 ?
            $a[] = Html::a($this->submittedTickets, [
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
        return '';
    }

    public function validateRunningTickets($attribute, $params)
    {
        $this->runningTickets != 0 ? $this->addError($attribute, \Yii::t('exams', 'Exam edit is disabled while there {n,plural,=1{is one ticket} other{are # tickets}} in "Running" state.', [
            'n' => $this->runningTickets
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
