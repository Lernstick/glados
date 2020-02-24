<?php

use yii\db\Migration;
use app\models\Exam;
use app\models\ExamSetting;
use app\models\ExamSettingAvail;

/**
 * Class m200212_120339_screen_capture
 */
class m200212_120339_screen_capture extends Migration
{
    public $examTable = 'exam';
    public $settingTable = 'exam_setting';
    public $availableSettingTable = 'exam_setting_avail';
    public $historyTable = 'history';
    public $translationTable = 'translation';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

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

    public function postProcessingUp()
    {   return [
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
                'description_id' => $this->integer(11)->notNull(),
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
            'description' => yiit('exam_setting_avail', 'TODO'),
        ]);
        $screen_capture->save(false);
        $screen_capture->refresh();

        // create screen_capture_command
        $screen_capture_command = new ExamSettingAvail([
            'key' => 'screen_capture_command',
            'name' => yiit('exam_setting_avail', 'Command'),
            'type' => 'ntext',
            //'default' => 'recordmydesktop --no-sound --fps 10 --on-the-fly-encoding --no-frame --v_quality 10',
            'default' => 'ffmpeg -f x11grab -r 10 -s 1920x1080 -i :0 -c:v libx264 -b:v 300k -an'
            'description' => yiit('exam_setting_avail', 'The actual command that is executed to capture the screen. This will <b>overwrite</b> all other settings.'),
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_command->save(false);

        // create screen_capture_fps
        $screen_capture_fps = new ExamSettingAvail([
            'key' => 'screen_capture_fps',
            'name' => yiit('exam_setting_avail', 'FPS'),
            'type' => 'integer',
            'default' => 10,
            'description' => yiit('exam_setting_avail', 'Frames per second. A positive number denoting the desired number framerate.'),
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_fps->save(false);

        // create screen_capture_fps
        $screen_capture_quality = new ExamSettingAvail([
            'key' => 'screen_capture_quality',
            'name' => yiit('exam_setting_avail', 'Quality'),
            'type' => 'percent',
            'default' => 0.7,
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_quality->save(false);

        // create screen_capture_fps
        $screen_capture_chunk = new ExamSettingAvail([
            'key' => 'screen_capture_chunk',
            'name' => yiit('exam_setting_avail', 'Chunk length'),
            'type' => 'integer',
            'default' => 30,
            'description' => yiit('exam_setting_avail', 'The length of one chunk in seconds.'),
            'belongs_to' => $screen_capture->id,
        ]);
        $screen_capture_chunk->save(false);

        $this->addColumn($this->historyTable, 'type', $this->boolean()->notNull()->defaultValue(0));

        // Default setting
        $this->insert($this->settingTable, [
            'key' => 'screenshots',
            'value' => true,
        ]);

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

            $found = false;
            foreach ($this->migrateFields as $field => $belongs_to) {
                if (array_key_exists($field, $settings)) {
                    if (array_key_exists($field, $this->postProcessingDown())) {
                        $settings[$field] = $this->postProcessingDown()[$field]($settings[$field]);
                        $found = true;
                    }
                }
            }

            if ($found) {
                Yii::$app->db->createCommand()->update($this->examTable,
                    $settings,
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

        $this->alterColumn($this->translationTable, 'en', $this->string(255)->notNull());
        $this->alterColumn($this->translationTable, 'de', $this->string(255));

        $this->dropColumn($this->historyTable, 'type');
    }

}
