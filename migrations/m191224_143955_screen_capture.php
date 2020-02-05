<?php

use yii\db\Migration;
use app\models\ExamSettingAvail;

/**
 * Class m191224_143955_screen_capture
 */
class m191224_143955_screen_capture extends Migration
{
    public $examTable = 'exam';
    public $scTable = 'screen_capture';
    public $settingTable = 'exam_setting';
    public $availableSettingTable = 'exam_setting_avail';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        // create the screen_capture table
        if ($this->db->schema->getTableSchema($this->scTable, true) === null) {
            $this->createTable($this->scTable, [
                'id' => $this->primaryKey(),
                'enabled' => $this->boolean()->notNull()->defaultValue(0),
                'quality' => $this->integer(11)->notNull()->defaultValue(100),
                'command' => $this->text(),
            ], $this->tableOptions);

            $this->addColumn($this->examTable, 'screen_capture_id', $this->integer(11));
            $this->createIndex('idx-screen_capture_id', $this->examTable, 'screen_capture_id');
        }

        $this->addForeignKey(
            'fk-exam-screen_capture_id',
            $this->examTable,
            'screen_capture_id',
            $this->scTable,
            'id',
            'CASCADE',
            'CASCADE'
        );

        // create the settings table
        if ($this->db->schema->getTableSchema($this->settingTable, true) === null) {
            $this->createTable($this->settingTable, [
                'id' => $this->primaryKey(),
                'key' => $this->text(),
                'value' => $this->text(),
                'belongs_to' => $this->integer(11),
                'exam_id' => $this->integer(11)->notNull(),
            ], $this->tableOptions);

            $this->createIndex('idx-exam_id', $this->settingTable, 'exam_id');
        }

        // create the available settings table
        if ($this->db->schema->getTableSchema($this->availableSettingTable, true) === null) {
            $this->createTable($this->availableSettingTable, [
                'id' => $this->primaryKey(),
                'key' => $this->text(),
                'type' => $this->text(),
                'default' => $this->text(),
                'description_data' => $this->string(1024)->defaultValue(null),
                'description_id' => $this->integer(11)->notNull(),
                'name_data' => $this->string(1024)->defaultValue(null),
                'name_id' => $this->integer(11)->notNull(),
                'belongs_to' => $this->integer(11),
            ], $this->tableOptions);
        }

        $this->addForeignKey(
            'fk-exam_setting_exam_id',
            $this->settingTable,
            'exam_id',
            $this->examTable,
            'id',
            'CASCADE',
            'CASCADE'
        );

        // create libre_autosave
        $libre_autosave = new ExamSettingAvail([
            'key' => 'libre_autosave',
            'name' => yiit('exams', 'Libreoffice: Save AutoRecovery information'),
            'type' => 'boolean',
            'default' => true,
            'description' => yiit('exams', 'Check to <b>save recovery information automatically every <code>n</code> minutes</b>. This command saves the information necessary to restore the current document in case of a crash (default location: <code>/home/user/.config/libreoffice/4/tmp</code>). Additionally, in case of a crash LibreOffice tries automatically to save AutoRecovery information for all open documents, if possible. (See <a target="_blank" href="https://help.libreoffice.org/Common/Saving_Documents_Automatically">LibreOffice Help</a>)'),
        ]);
        $libre_autosave->save(false);
        $libre_autosave->refresh();

        // create libre_autosave_interval
        $libre_autosave_interval = new ExamSettingAvail([
            'key' => 'libre_autosave_interval',
            'name' => yiit('exams', 'Libreoffice: Save AutoRecovery information interval'),
            'type' => 'integer',
            'default' => 10,
            'belongs_to' => $libre_autosave->id,
        ]);
        $libre_autosave_interval->save(false);

        // create libre_autosave_path
        $libre_autosave_path = new ExamSettingAvail([
            'key' => 'libre_autosave_path',
            'name' => yiit('exams', 'Libreoffice: Save AutoRecovery information path'),
            'type' => 'string',
            'default' => '/home/user/.config/libreoffice/4/user/tmp',
            'belongs_to' => $libre_autosave->id,
        ]);
        $libre_autosave_path->save(false);

        // libre_createbackup
        $libre_createbackup = new ExamSettingAvail([
            'key' => 'libre_createbackup',
            'name' => yiit('exams', 'Libreoffice: Always create backup copy'),
            'type' => 'boolean',
            'default' => true,
            'description' => yiit('exams', 'If the <b>Always create backup copy</b> option is selected, the old version of the file is saved to the backup directory whenever you save the current version of the file. The backup copy has the same name as the document, but the extension is <code>.BAK</code>. If the backup folder (default location: <code>/home/user/.config/libreoffice/4/backup</code>) already contains such a file, it will be overwritten without warning. (See <a target="_blank" href="https://help.libreoffice.org/Common/Saving_Documents_Automatically">LibreOffice Help</a>)'),
        ]);
        $libre_createbackup->save(false);
        $libre_createbackup->refresh();

        // libre_createbackup_path
        $libre_createbackup_path = new ExamSettingAvail([
            'key' => 'libre_createbackup_path',
            'name' => yiit('exams', 'Libreoffice: Always create backup copy path'),
            'type' => 'string',
            'default' => '/home/user/.config/libreoffice/4/user/backup',
            'belongs_to' => $libre_createbackup->id,
        ]);
        $libre_createbackup_path->save(false);

        // create max_brightness
        $max_brightness = new ExamSettingAvail([
            'key' => 'max_brightness',
            'name' => yiit('exams', 'Maximum brightness'),
            'type' => 'range',
            'default' => 100,
            'description' => yiit('exams', 'Maximum screen brightness in percent. Notice that some devices have buttons to adjust screen brightness on hardware level. This cannot be controlled by this setting.'),
        ]);
        $max_brightness->save(false);

        // create screenshots
        $screenshots = new ExamSettingAvail([
            'key' => 'screenshots',
            'name' => yiit('exams', 'Take Screenshots'),
            'type' => 'boolean',
            'default' => true,
            'description' => yiit('exams', 'If set, the system will <b>create screenshots every n minutes</b>. The Interval can be set in minutes. Those screenshots will appear in the Ticket view under the register "Screenshots". When generating exam results, they can also be included.'),
        ]);
        $screenshots->save(false);
        $screenshots->refresh();

        // create screenshots_interval
        $screenshots_interval = new ExamSettingAvail([
            'key' => 'screenshots_interval',
            'name' => yiit('exams', 'Screenshot interval'),
            'type' => 'integer',
            'default' => 5,
            'belongs_to' => $screenshots->id,
        ]);
        $screenshots_interval->save(false);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        if ($this->db->schema->getTableSchema($this->scTable, true) !== null) {
            $this->dropForeignKey('fk-exam-screen_capture_id', $this->examTable);
            $this->dropColumn($this->examTable, 'screen_capture_id');
            $this->dropTable($this->scTable);
        }

        if ($this->db->schema->getTableSchema($this->settingTable, true) !== null) {
            $this->dropForeignKey('fk-exam_setting_exam_id', $this->settingTable);
            $this->dropTable($this->settingTable);
        }

        if ($this->db->schema->getTableSchema($this->availableSettingTable, true) !== null) {
            $this->dropTable($this->availableSettingTable);
        }

    }
}
