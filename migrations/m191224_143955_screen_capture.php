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
                'exam_id' => $this->integer(11)->notNull(),
            ], $this->tableOptions);
        }

        // create the available settings table
        if ($this->db->schema->getTableSchema($this->availableSettingTable, true) === null) {
            $this->createTable($this->availableSettingTable, [
                'id' => $this->primaryKey(),
                'key' => $this->text(),
                'type' => $this->text(),
                'description_data' => $this->string(1024)->defaultValue(null),
                'description_id' => $this->integer(11)->notNull(),
                'name_data' => $this->string(1024)->defaultValue(null),
                'name_id' => $this->integer(11)->notNull(),
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
            'type' => 'multi',
            'description' => yiit('exams', 'Check to <b>save recovery information automatically every <code>n</code> minutes</b>. This command saves the information necessary to restore the current document in case of a crash (default location: <code>/home/user/.config/libreoffice/4/tmp</code>). Additionally, in case of a crash LibreOffice tries automatically to save AutoRecovery information for all open documents, if possible. (See <a target="_blank" href="https://help.libreoffice.org/Common/Saving_Documents_Automatically">LibreOffice Help</a>).'),
        ]);
        $libre_autosave->save(false);

        // create libre_autosave_interval
        $libre_autosave_interval = new ExamSettingAvail([
            'key' => 'libre_autosave_interval',
            'name' => yiit('exams', 'Libreoffice: Save AutoRecovery information interval'),
            'type' => 'integer',
        ]);
        $libre_autosave_interval->save(false);

        // create libre_autosave_path
        $libre_autosave_path = new ExamSettingAvail([
            'key' => 'libre_autosave_path',
            'name' => yiit('exams', 'Libreoffice: Save AutoRecovery information path'),
            'type' => 'string',
        ]);
        $libre_autosave_path->save(false);

        // create max_brightness
        $max_brightness = new ExamSettingAvail([
            'key' => 'max_brightness',
            'name' => yiit('exams', 'Maximum brightness'),
            'type' => 'range',
            'description' => yiit('exams', 'Maximum screen brightness in percent. Notice that some devices have buttons to adjust screen brightness on hardware level. This cannot be controlled by this setting.'),
        ]);
        $max_brightness->save(false);

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
