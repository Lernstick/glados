<?php

use yii\db\Migration;
use app\models\Activity;
use app\models\Ticket;
use app\models\Translation;

/**
 * Class m190531_162014_i18n_migrate
 * 
 * This migration creates all database entries and translations
 */
class m190531_162014_i18n_migrate extends Migration
{

    public $activitiesTable = 'activity';
    public $ticketTable = 'ticket';

    // .*? makes id lazy instead of greedy
    public $descriptionRegexes = [
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

    public $client_stateRegexes = [
        '/^Backup failed: rdiff-backup failed \(retval: (.*?)\)/' => [
            0 => 'Backup failed: rdiff-backup failed (retval: {retval})',
            1 => 'retval',
        ],
    ];

    /**
     * These entries are only here, such that they will never be removed
     * from the translation files in messages/LANG/*.php
     */
    public function dummy() {
        // for backward compatibility
        yiit('activity', 'Client state changed: {old} -> {new}');
        yiit('ticket', 'Client not seen yet');
    }

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $models = Activity::find()->all();
        $nr = Activity::find()->count();
        $i = 1;
        foreach ($models as $model) {
            echo "Activities: Migrating database record " . $i . "/" . $nr . "\r";

            $en = $model->description_old;
            $dummy_params = [];

            foreach ($this->descriptionRegexes as $regex => $keys) {
                $matches = [];
                if (preg_match($regex, $model->description_old, $matches) == 1) {
                    $en = $keys[0];
                    array_shift($keys);
                    array_shift($matches);
                    $vals = array_map(function ($e) {
                        return '{' . $e . '}';
                    }, $keys);

                    $dummy_params = array_combine($keys, $vals);
                    $model->description_params = array_combine($keys, $matches);
                    break 1;
                }
            }

            $existing = Translation::find()->where(['en' => $en])->one();

            if ($existing === null || $existing === false) {
                // TODO: loop through all languages
                $new = new Translation([
                    'en' => \Yii::t('activity', $en, $dummy_params, 'en'),
                    'de' => \Yii::t('activity', $en, $dummy_params, 'de'),
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

        $models = Ticket::find()->all();
        $nr = Ticket::find()->count();
        $i = 1;
        foreach ($models as $model) {
            echo "Tickets: Migrating database record " . $i . "/" . $nr . "\r";

            $en = $model->client_state_old;
            $dummy_params = [];

            foreach ($this->client_stateRegexes as $regex => $keys) {
                $matches = [];
                if (preg_match($regex, $model->client_state_old, $matches) == 1) {
                    $en = $keys[0];
                    array_shift($keys);
                    array_shift($matches);
                    $vals = array_map(function ($e) {
                        return '{' . $e . '}';
                    }, $keys);

                    $dummy_params = array_combine($keys, $vals);
                    $model->client_state_params = array_combine($keys, $matches);
                    break 1;
                }
            }

            $existing = Translation::find()->where(['en' => $en])->one();

            if ($existing === null || $existing === false) {
                // TODO: loop through all languages
                $new = new Translation([
                    'en' => \Yii::t('live_data', $en, $dummy_params, 'en'),
                    'de' => \Yii::t('live_data', $en, $dummy_params, 'de'),
                ]);
                $new->save();
                $model->client_state_id = $new->id;
            } else {
                $model->client_state_id = $existing->id;
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
            echo "Activities: Migrating database record " . $i . "/" . $nr . " down\r";
            Yii::$app->db->createCommand()->update($this->activitiesTable, [
                'description_new' => \Yii::t('activity', $model->description, $model->description_params, 'en'),
            ], ['id' => $model->id])->execute();
            $i = $i + 1;
        }
        echo "\n";

        $models = Ticket::find()->where(['client_state_new' => null])->all();
        $nr = Ticket::find()->where(['client_state_new' => null])->count();
        $i = 1;
        foreach ($models as $model) {
            echo "Tickets: Migrating database record " . $i . "/" . $nr . " down\r";
            Yii::$app->db->createCommand()->update($this->ticketTable, [
                'client_state_new' => \Yii::t('ticket', $model->client_state, $model->client_state_params, 'en'),
            ], ['id' => $model->id])->execute();
            $i = $i + 1;
        }
        echo "\n";
    }
}
