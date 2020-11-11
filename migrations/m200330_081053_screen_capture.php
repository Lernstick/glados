<?php

use yii\db\Migration;
use app\models\Exam;
use app\models\ExamSetting;
use app\models\ExamSettingAvail;

/**
 * Class m200330_081053_screen_capture
 */
class m200330_081053_screen_capture extends Migration
{
    public $examTable = 'exam';
    public $ticketTable = 'ticket';
    public $settingTable = 'exam_setting';
    public $availableSettingTable = 'exam_setting_avail';
    public $historyTable = 'history';
    public $translationTable = 'translation';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
    public $execTable = 'remote_execution';

    /**
     * @array key value pair of fields to migrate, where key defines the olf field 
     * name in the "exam" table, and the value relation to which settings the setting
     * belongs to.
     */
    public $migrateFields = [
        'screenshots' => null,
        'screenshots_interval' => 'screenshots',
        'libre_autosave' => null,
        'libre_autosave_interval' => 'libre_autosave',
        'libre_autosave_path' => 'libre_autosave',
        'libre_createbackup' => null,
        'libre_createbackup_path' => 'libre_createbackup',
        'max_brightness' => null,
        'url_whitelist' => null,
    ];

    /**
     * @return false|mixed if returned false, the settings entry is not written
     */
    public function postProcessingUp()
    {   return [
            'url_whitelist' => function($v){return $v == '' ? false : $v;},
            'max_brightness' => function($v){return $v/100;},
        ];
    }

    public function postProcessingDown()
    {   return [
            'max_brightness' => function($v){return $v*100;},
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->preMigrationUp();
        $this->migrateDataUp();
        $this->postMigrationUp();
    }

    public function preMigrationUp() {

        // create the settings table
        if ($this->db->schema->getTableSchema($this->settingTable, true) === null) {
            $this->createTable($this->settingTable, [
                'id' => $this->primaryKey(),
                'key' => $this->text(),
                'value' => $this->text(),
                'belongs_to' => $this->integer(11),
                'exam_id' => $this->integer(11)->null(),
            ], $this->tableOptions);

            $this->createIndex('idx-exam_id', $this->settingTable, 'exam_id');

            $this->addForeignKey(
                'fk-exam_setting_exam_id',
                $this->settingTable,
                'exam_id',
                $this->examTable,
                'id',
                'CASCADE',
                'CASCADE'
            );

        }

        // create the available settings table
        if ($this->db->schema->getTableSchema($this->availableSettingTable, true) === null) {
            $this->createTable($this->availableSettingTable, [
                'id' => $this->primaryKey(),
                'key' => $this->text(),
                'type' => $this->text(),
                'default' => $this->text(),
                'description_data' => $this->string(1024)->defaultValue(null),
                'description_id' => $this->integer(11)->notNull()->defaultValue(0),
                'name_data' => $this->string(1024)->defaultValue(null),
                'name_id' => $this->integer(11)->notNull(),
                'belongs_to' => $this->integer(11),
            ], $this->tableOptions);
        }

        $this->alterColumn($this->translationTable, 'en', $this->string(1024)->notNull());
        $this->alterColumn($this->translationTable, 'de', $this->string(1024));

        // create libre_autosave
        $libre_autosave = new ExamSettingAvail([
            'key' => 'libre_autosave',
            'name' => yiit('exam_setting_avail', 'Libreoffice: Save AutoRecovery information'),
            'type' => 'boolean',
            'default' => true,
            'description' => yiit('exam_setting_avail', 'Check to <b>save recovery information automatically every <code>n</code> minutes</b>. This command saves the information necessary to restore the current document in case of a crash (default location: <code>/home/user/.config/libreoffice/4/tmp</code>). Additionally, in case of a crash LibreOffice tries automatically to save AutoRecovery information for all open documents, if possible. (See <a target="_blank" href="https://help.libreoffice.org/Common/Saving_Documents_Automatically">LibreOffice Help</a>)'),
        ]);
        $libre_autosave->save(false);
        $libre_autosave->refresh();

        // create libre_autosave_interval
        $libre_autosave_interval = new ExamSettingAvail([
            'key' => 'libre_autosave_interval',
            'name' => yiit('exam_setting_avail', 'Interval'),
            'type' => 'integer',
            'default' => 10,
            'belongs_to' => $libre_autosave->id,
        ]);
        $libre_autosave_interval->save(false);

        // create libre_autosave_path
        $libre_autosave_path = new ExamSettingAvail([
            'key' => 'libre_autosave_path',
            'name' => yiit('exam_setting_avail', 'Path'),
            'type' => 'text',
            'default' => '/home/user/.config/libreoffice/4/user/tmp',
            'belongs_to' => $libre_autosave->id,
        ]);
        $libre_autosave_path->save(false);

        // libre_createbackup
        $libre_createbackup = new ExamSettingAvail([
            'key' => 'libre_createbackup',
            'name' => yiit('exam_setting_avail', 'Libreoffice: Always create backup copy'),
            'type' => 'boolean',
            'default' => true,
            'description' => yiit('exam_setting_avail', 'If the <b>Always create backup copy</b> option is selected, the old version of the file is saved to the backup directory whenever you save the current version of the file. The backup copy has the same name as the document, but the extension is <code>.BAK</code>. If the backup folder (default location: <code>/home/user/.config/libreoffice/4/backup</code>) already contains such a file, it will be overwritten without warning. (See <a target="_blank" href="https://help.libreoffice.org/Common/Saving_Documents_Automatically">LibreOffice Help</a>)'),
        ]);
        $libre_createbackup->save(false);
        $libre_createbackup->refresh();

        // libre_createbackup_path
        $libre_createbackup_path = new ExamSettingAvail([
            'key' => 'libre_createbackup_path',
            'name' => yiit('exam_setting_avail', 'Path'),
            'type' => 'text',
            'default' => '/home/user/.config/libreoffice/4/user/backup',
            'belongs_to' => $libre_createbackup->id,
        ]);
        $libre_createbackup_path->save(false);

        // create max_brightness
        $max_brightness = new ExamSettingAvail([
            'key' => 'max_brightness',
            'name' => yiit('exam_setting_avail', 'Maximum brightness'),
            'type' => 'percent',
            'default' => 1,
            'description' => yiit('exam_setting_avail', 'Maximum screen brightness in percent. Notice that some devices have buttons to adjust screen brightness on hardware level. This cannot be controlled by this setting.'),
        ]);
        $max_brightness->save(false);

        // create screenshots
        $screenshots = new ExamSettingAvail([
            'key' => 'screenshots',
            'name' => yiit('exam_setting_avail', 'Take Screenshots'),
            'type' => 'boolean',
            'default' => true,
            'description' => yiit('exam_setting_avail', 'If set, the system will <b>create screenshots every n minutes</b>. The Interval can be set in minutes. Those screenshots will appear in the Ticket view under the register "Screenshots". When generating exam results, they can also be included.'),
        ]);
        $screenshots->save(false);
        $screenshots->refresh();

        // create screenshots_interval
        $screenshots_interval = new ExamSettingAvail([
            'key' => 'screenshots_interval',
            'name' => yiit('exam_setting_avail', 'Interval'),
            'type' => 'integer',
            'default' => 5,
            'belongs_to' => $screenshots->id,
        ]);
        $screenshots_interval->save(false);

        // create url_whitelist
        $url_whitelist = new ExamSettingAvail([
            'key' => 'url_whitelist',
            'name' => \Yii::t('exam_setting_avail', 'HTTP URL Whitelist'),
            'type' => 'ntext',
            'default' => '',
            'description' => \Yii::t('exam_setting_avail', 'URLs given in this list will be allowed to visit by the exam student during the exam. Notice, due to this date, only URLs starting with <code>http://</code> are supported, therefore https://</code> URLs will be ignored. The URLs should be provided newline separated. The provided URLs are allowed even if the Firewall is enabled.'),
        ]);
        $url_whitelist->save(false);

        // create screenshots
        $screen_capture = new ExamSettingAvail([
            'key' => 'screen_capture',
            'name' => yiit('exam_setting_avail', 'Screen capturing'),
            'type' => 'boolean',
            'default' => true,
            'description' => yiit('exam_setting_avail', 'Activates screen capturing, <b>a digital recording of computer screen output</b>. The video output can be accessed in the overview of the corresponding ticket. This produces a lot of data and traffic and is usually activated in exams that need to be recourse-proof. It serves as well as a backstop for unsaved documents. Various adjustments can be configured. If keylogger is enabled as well, the keystrokes will be aggregated into subtitles of the video stream (see image).<br><img src="../../howto/img/screen_capture.gif">'),
        ]);
        $screen_capture->save(false);
        $screen_capture->refresh();

        // create screen_capture_command
        $screen_capture_command = new ExamSettingAvail([
            'key' => 'screen_capture_command',
            'name' => yiit('exam_setting_avail', 'Command'),
            'type' => 'ntext',
            'default' => 'ffmpeg -f x11grab -r "${fps}" -s "${resolution}" -i :0 -vf "scale=\'max(1280,iw/2)\':-2" \
  -c:v h264 -b:v "${bitrate}" -profile:v baseline -pix_fmt:v yuv420p -an \
  -flags +cgop -g "${gop}" -hls_playlist_type event -hls_time "${chunk}" \
  -strftime 1 -hls_flags append_list+program_date_time -master_pl_name "${master}" \
  -hls_segment_filename "video%s.ts" -loglevel level+info -nostats "${playlist}"',
            'description' => yiit('exam_setting_avail', 'The actual command that is executed to capture the screen. This will <b>overwrite</b> all other settings.'),
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_command->save(false);

        // create screen_capture_fps
        $screen_capture_fps = new ExamSettingAvail([
            'key' => 'screen_capture_fps',
            'name' => yiit('exam_setting_avail', 'FPS'),
            'type' => 'decimal',
            'default' => 10,
            'description' => yiit('exam_setting_avail', 'Set frame rate (Hz value or fraction). A positive number denoting the desired framerate.'),
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_fps->save(false);

        // create screen_capture_chunk
        $screen_capture_chunk = new ExamSettingAvail([
            'key' => 'screen_capture_chunk',
            'name' => yiit('exam_setting_avail', 'Chunk length'),
            'type' => 'decimal',
            'default' => 10,
            'description' => yiit('exam_setting_avail', 'The length of one chunk in seconds (can be a fraction).'),
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_chunk->save(false);

        // create screen_capture_bitrate
        $screen_capture_bitrate = new ExamSettingAvail([
            'key' => 'screen_capture_bitrate',
            'name' => yiit('exam_setting_avail', 'Bitrate'),
            'type' => 'text',
            'default' => '300k',
            'description' => yiit('exam_setting_avail', 'The bitrate (<code>bitrate = filesize / duration</code>) is the amount of bits that should be produced per second. Use abbreviations like <code>k</code> for kBit/s and <code>m</code> for MBit/s. Notice that the default value <code>300k</code>, will for a 3h exam end up in approximately <code>400MB</code> video stream data per exam.'),
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_bitrate->save(false);

        // create screen_capture_path
        $screen_capture_path = new ExamSettingAvail([
            'key' => 'screen_capture_path',
            'name' => yiit('exam_setting_avail', 'Path'),
            'type' => 'text',
            'default' => "/home/user/.ScreenCapture",
            'description' => yiit('exam_setting_avail', 'The path to save output files. This is also the working directory of the command above.'),
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_path->save(false);

        // create screen_capture_overflow_threshold
        $screen_capture_overflow_threshold = new ExamSettingAvail([
            'key' => 'screen_capture_overflow_threshold',
            'name' => yiit('exam_setting_avail', 'Overflow threshold'),
            'type' => 'text',
            'default' => "500m",
            'description' => yiit('exam_setting_avail', 'Since on the client system there is a lot of data produced in memory due to screen capturing leading to the system to being out of memory, you can define a threshold, such that when reached, the client system starts to delete screen capture files that are not yet fetched by the server in order to free memory.<br>Examples: <ul><li><code>25%</code>: the threshold is reached when 25% of the total disk space is filled with screen capture files and not yet fetched by the server.</li><li><code>500m</code>: a fixed threshold, that is reached when more than 500 Megabytes of screen capture files are produced and not yet fetched by the server.</li><li><code>0m</code>/<code>0%</code>: disables the threshold, never delete anything.</li></ul>'),
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_overflow_threshold->save(false);

        // create keylogger
        $keylogger = new ExamSettingAvail([
            'key' => 'keylogger',
            'name' => yiit('exam_setting_avail', 'Keylogger'),
            'type' => 'boolean',
            'default' => true,
            'description' => yiit('exam_setting_avail', 'Activates <b>recording (logging) of the keys struck</b> on the students keyboard. This is usually used to retrace the students steps and serves as an additional safety net when the student forgets to save his/her workings. If screen capturing is enabled as well, the keystrokes will be aggregated into subtitles of the video stream.'),
        ]);
        $keylogger->save(false);
        $keylogger->refresh();

        // create keylogger_keymap
        $keylogger_keymap = new ExamSettingAvail([
            'key' => 'keylogger_keymap',
            'name' => yiit('exam_setting_avail', 'Keymap'),
            'type' => 'text',
            'default' => 'auto',
            'description' => yiit('exam_setting_avail', 'The input keymap for processing pressed keys. Use <code>auto</code> such that the system determines the keymap itself - if it cannot be determined, the system falls back to the <code>en_US</code> keymap.'),
            'belongs_to' => $keylogger->id,
        ]);
        $keylogger_keymap->save(false);
        $keylogger_keymap->refresh();

        // create keylogger_path
        $keylogger_path = new ExamSettingAvail([
            'key' => 'keylogger_path',
            'name' => yiit('exam_setting_avail', 'Path'),
            'type' => 'text',
            'default' => '/home/user/.Keylogger',
            'description' => yiit('exam_setting_avail', 'The path to save output files.'),
            'belongs_to' => $keylogger->id,
        ]);
        $keylogger_path->save(false);
        $keylogger_path->refresh();

        $this->addColumn($this->historyTable, 'type', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->ticketTable, 'sc_size', $this->integer(11)->notNull()->defaultValue(0));

        // Default setting
        $this->insert($this->settingTable, [
            'key' => 'screenshots',
            'value' => true,
        ]);

        // create the command table
        if ($this->db->schema->getTableSchema($this->execTable, true) === null) {
            $this->createTable($this->execTable, [
                'id' => $this->primaryKey(),
                'cmd' => $this->string(255)->notNull(),
                'env' => $this->string(255)->notNull(),
                'host' => $this->string(255)->notNull(),
                'requested_at' => $this->double('14,4')->notNull(),
            ], $this->tableOptions);

            /* add unique index for cmd, env and host combined */
            $this->createIndex('uc-cmd-env-host', $this->execTable, ['cmd', 'env', 'host'], true);
        }

    }

    public function migrateDataUp()
    {
        $models = Exam::find()->all();
        $nr = Exam::find()->count();

        $i = 1;
        foreach ($models as $model) {
            echo "Table " . $this->examTable . ": Migrating database record " . $i . "/" . $nr . "\r";

            foreach ($this->migrateFields as $field => $belongs_to) {
                if (array_key_exists($field, $this->postProcessingUp())) {
                    $value = $this->postProcessingUp()[$field]($model->{$field});
                } else {
                    $value = $model->{$field};
                }

                // skip the entry if the value evalutes to false (see [[postProcessingUp]])
                if ($value !== false) {
                    Yii::$app->db->createCommand()->insert($this->settingTable, [
                        'key' => $field,
                        'value' => $value,
                        'exam_id' => $model->id,
                        'belongs_to' => $belongs_to !== null ? $lastId : null,
                    ])->execute();
                    if ($belongs_to === null) {
                        $lastId = Yii::$app->db->getLastInsertID();
                    }
                }
            }
            $i = $i + 1;
        }

        $nr = count($this->migrateFields);
        $i = 1;
        foreach ($this->migrateFields as $field => $belongs_to) {
            echo "Table " . $this->historyTable . " - " . $field . ": Migrating database field " . $i . "/" . $nr . "\r";
            Yii::$app->db->createCommand()->update($this->historyTable, [
                'column' => 'exam_setting.' . $field
            ], [
                'table' => 'exam',
                'column' => $field,
            ])->execute();
            $i = $i + 1;
        }

        // special post processing of max_brightness
        Yii::$app->db->createCommand()->update($this->historyTable, [
            'old_value' => new yii\db\Expression('`old_value`/100'),
            'new_value' => new yii\db\Expression('`new_value`/100'),
        ], [
            'table' => 'exam',
            'column' => 'exam_setting.max_brightness',
        ])->execute();

    }

    public function postMigrationUp()
    {
        foreach ($this->migrateFields as $field => $belongs_to) {
            $this->dropColumn($this->examTable, $field);
        }
    }


    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->preMigrationDown();
        $this->migrateDataDown();
        $this->postMigrationDown();
    }

    public function preMigrationDown()
    {
        $this->addColumn($this->examTable, 'screenshots', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'screenshots_interval', $this->integer(11)->notNull()->defaultValue(5));
        $this->addColumn($this->examTable, 'libre_autosave', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'libre_createbackup', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'libre_autosave_interval', $this->integer(11)->notNull()->defaultValue(10));
        $this->addColumn($this->examTable, 'libre_autosave_path', $this->string(255)->notNull()->defaultValue('/home/user/.config/libreoffice/4/user/tmp'));
        $this->addColumn($this->examTable, 'libre_createbackup_path', $this->string(255)->notNull()->defaultValue('/home/user/.config/libreoffice/4/user/backup'));
        $this->addColumn($this->examTable, 'max_brightness', $this->integer(11)->notNull()->defaultValue(100));
        $this->addColumn($this->examTable, 'url_whitelist', $this->string(512)->Null()->defaultValue(Null));
    }

    public function migrateDataDown()
    {
        $models = Exam::find()->all();
        $nr = Exam::find()->count();

        $i = 1;
        foreach ($models as $model) {
            echo "Table " . $this->examTable . ": Migrating database record down " . $i . "/" . $nr . "\r";

            $settings = array_combine(
                array_map(function($v){return $v->key;}, $model->exam_setting),
                array_map(function($v){return $v->value;}, $model->exam_setting)
            );

            // post processing down
            $found = false;
            foreach ($this->migrateFields as $field => $belongs_to) {
                if (array_key_exists($field, $settings)) {
                    if (array_key_exists($field, $this->postProcessingDown())) {
                        $settings[$field] = $this->postProcessingDown()[$field]($settings[$field]);
                        $found = true;
                    }
                }
            }

            // only set these field that have columns in the table exam
            $updateSettings = [];
            foreach ($this->migrateFields as $key => $belongs_to) {
                if (array_key_exists($key, $settings)) {
                    $updateSettings[$key] = $settings[$key];
                }
            }

            if ($found && !empty($updateSettings)) {
                Yii::$app->db->createCommand()->update($this->examTable,
                    $updateSettings,
                    ['id' => $model->id]
                )->execute();
            }

            $i = $i + 1;
        }

        $nr = count($this->migrateFields);
        $i = 1;
        foreach ($this->migrateFields as $field => $belongs_to) {
            echo "Table " . $this->historyTable . " - " . $field . ": Migrating database field down " . $i . "/" . $nr . "\r";
            Yii::$app->db->createCommand()->update($this->historyTable, [
                'column' => $field,
            ], [
                'table' => 'exam',
                'column' => 'exam_setting.' . $field
            ])->execute();
            $i = $i + 1;
        }

        // special post processing of max_brightness
        Yii::$app->db->createCommand()->update($this->historyTable, [
            'old_value' => new yii\db\Expression('`old_value`*100'),
            'new_value' => new yii\db\Expression('`new_value`*100'),
        ], [
            'table' => 'exam',
            'column' => 'max_brightness',
        ])->execute();

    }

    public function postMigrationDown()
    {
        if ($this->db->schema->getTableSchema($this->settingTable, true) !== null) {
            $this->dropForeignKey('fk-exam_setting_exam_id', $this->settingTable);
            $this->dropTable($this->settingTable);
        }

        if ($this->db->schema->getTableSchema($this->availableSettingTable, true) !== null) {
            $this->dropTable($this->availableSettingTable);
        }

        //commented, because in mysql, the fields would be truncated and mysql does not allow that
        //$this->alterColumn($this->translationTable, 'en', $this->string(255)->notNull());
        //$this->alterColumn($this->translationTable, 'de', $this->string(255));

        $this->dropColumn($this->historyTable, 'type');
        $this->dropColumn($this->ticketTable, 'sc_size');

        if ($this->db->schema->getTableSchema($this->execTable, true) !== null) {
            /* drop the combined unique index from exec table */
            $this->dropIndex('uc-cmd-env-host', $this->execTable);

            $this->dropTable($this->execTable);
        }
    }

}
