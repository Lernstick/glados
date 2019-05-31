<?php

use yii\db\Migration;
use app\models\Activity;
use app\models\ActivityDescription;

/**
 * Class m190531_162014_i18n_migrate
 * 
 * This migration creates all database entries and translations
 */
class m190531_162014_i18n_migrate extends Migration
{

    public $activitiesTable = 'activity';
    public $descriptionTable = 'tr_activity_description';
    public $descriptionColumn = 'description_id';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    // .*? makes id lazy instead of greedy
    public $regexes = [
        '/^Backup failed: rdiff-backup failed \(retval: (.*?)\)/' => [
            0 => 'Backup failed: rdiff-backup failed (retval: {retval})',
            1 => 'retval',
        ],
        '/^Client state changed: (.*?) \-\> (.*?)$/' => [
            0 => 'Client state changed: {old} -> {new}',
            1 => 'old',
            2 => 'new',
        ],
        '/^Download failed: rsync failed \(retval: (.*?)\)/' => [
            0 => 'Download failed: rsync failed (retval: {retval})',
            1 => 'retval',
        ],
        '/^Exam download aborted by (.*?) from (.*?) \(client side\)\./' => [
            0 => 'Exam download aborted by {ip} from {test_taker} (client side).',
            1 => 'ip',
            2 => 'test_taker',
        ],
        '/^Exam download finished by (.*?) from Ticket with token (.*?)\./' => [
            0 => 'Exam download finished by {ip} from Ticket with token {token}.',
            1 => 'ip',
            2 => 'token'
        ],
        '/^Exam download finished by (.*?) from (.*?)\./' => [
            0 => 'Exam download finished by {ip} from {test_taker}.',
            1 => 'ip',
            2 => 'test_taker'
        ],
        '/^Exam download successfully requested by (.*?) from Ticket with token (.*?)\./' => [
            0 => 'Exam download successfully requested by {ip} from Ticket with token {token}.',
            1 => 'ip',
            2 => 'token',
        ],
        '/^Exam download successfully requested by (.*?) from (.*?)\./' => [
            0 => 'Exam download successfully requested by {ip} from {test_taker}.',
            1 => 'ip',
            2 => 'token',
        ],
        '/^Exam finished by Ticket with token (.*?)\./' => [
            0 => 'Exam finished by Ticket with token {token}.',
            1 => 'token',
        ],
        '/^Exam finished by (.*?)\./' => [
            0 => 'Exam finished by {test_taker}.',
            1 => 'test_taker',
        ],
        '/^Restore failed: "(.*?)": No such file or directory\./' => [
            0 => 'Restore failed: "{file}": No such file or directory.',
            1 => 'file',
        ],
        '/^Restore failed: rdiff-backup failed \(retval: (.*?)\)/' => [
            0 => 'Restore failed: rdiff-backup failed (retval: {retval})',
            1 => 'retval',
        ],
        '/^Restore of (.*?) as it was as of (.*?) was successful\./' => [
            0 => 'Restore of {file} as it was as of {date} was successful.',
            1 => 'file',
            2 => 'date'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $models = Activity::find()->all();
        $nr = Activity::find()->count();
        $i = 1;
        foreach ($models as $model) {
            echo "Migrating database record " . $i . "/" . $nr . "\r";

            $en = $model->description_old;
            $dummy_params = [];

            foreach ($this->regexes as $regex => $keys) {
                $matches = [];
                if (preg_match($regex, $model->description_old, $matches) == 1) {
                    $en = $keys[0];
                    array_shift($keys);
                    array_shift($matches);
                    $vals = array_map(function ($e) {
                        return '{' . $e . '}';
                    }, $keys);

                    $dummy_params = array_combine($keys, $vals);
                    $model->params = array_combine($keys, $matches);
                    break 1;
                }
            }

            $existing = ActivityDescription::find()->where(['en' => $en])->one();

            if ($existing === null || $existing === false) {
                // TODO: loop through all languages
                $new = new ActivityDescription([
                    'en' => \Yii::t('activities', $en, $dummy_params, 'en'),
                    'de' => \Yii::t('activities', $en, $dummy_params, 'de'),
                ]);
                $new->save();
                $model->description_id = $new->id;
            } else {
                $model->description_id = $existing->id;
            }

            $model->update(false); // skipping validation as no user input is involved
            $i = $i + 1;
        }
        echo "\n";

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $models = Activity::find()->where(['description_new' => null])->all();
        $nr = Activity::find()->where(['description_new' => null])->count();
        $i = 1;
        foreach ($models as $model) {
            echo "Migrating database record " . $i . "/" . $nr . " down\r";
            Yii::$app->db->createCommand()->update($this->activitiesTable, [
                'description_new' => \Yii::t('activities', $model->description, $model->params, 'en'),
            ], ['id' => $model->id])->execute();
            $i = $i + 1;
        }
        echo "\n";
    }
}
