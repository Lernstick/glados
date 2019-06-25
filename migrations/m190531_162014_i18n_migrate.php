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
    public $regexes = [
        'description' => [
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
        ],
        'client_state' => [
            '/^Backup failed: rdiff-backup failed \(retval: (.*?)\)/' => [
                0 => 'Backup failed: rdiff-backup failed (retval: {retval})',
                1 => 'retval',
            ],
            '/^(.*?) reconnected \((.*?)\)\.$/' => [
                0 => '{interface} reconnected ({count}).',
                1 => 'interface',
                2 => 'count',
            ],
        ]
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
        // activity->description
        $models = Activity::find()->all();
        $nr = Activity::find()->count();
        $this->migrateTableFieldUp($this->activitiesTable, 'description', $models, $nr);

        // ticket->client_state
        $models = Ticket::find()->all();
        $nr = Ticket::find()->count();
        $this->migrateTableFieldUp($this->ticketTable, 'client_state', $models, $nr);

        // ticket->backup_state
        $this->migrateTableFieldUp($this->ticketTable, 'backup_state', $models, $nr);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // activity->description
        $models = Activity::find()->where(['description_new' => null])->all();
        $nr = Activity::find()->where(['description_new' => null])->count();
        $this->migrateTableFieldDown($this->activitiesTable, 'description', $models, $nr);

        // ticket->client_state
        $models = Ticket::find()->where(['client_state_new' => 'Client not seen yet'])->all();
        $nr = Ticket::find()->where(['client_state_new' => 'Client not seen yet'])->count();
        $this->migrateTableFieldDown($this->ticketTable, 'client_state', $models, $nr);

        // ticket->backup_state
        $models = Ticket::find()->where(['backup_state_new' => null])->all();
        $nr = Ticket::find()->where(['backup_state_new' => null])->count();
        $this->migrateTableFieldDown($this->ticketTable, 'backup_state', $models, $nr);
    }

    private function migrateTableFieldUp($table, $field, $models, $nr)
    {
        $dataField = $field . "_data";
        $idField = $field . "_id";
        $oldField = $field . "_old";
        $paramsField = $field . "_params";

        $i = 1;
        foreach ($models as $model) {
            echo "Table " . $table . ": Migrating database record " . $i . "/" . $nr . "\r";

            $en = $model->{$oldField};
            $dummy_params = [];

            if (isset($this->regexes[$field])) {
                foreach ($this->regexes[$field] as $regex => $keys) {
                    $matches = [];
                    if (preg_match($regex, $model->{$oldField}, $matches) == 1) {
                        $en = $keys[0];
                        array_shift($keys);
                        array_shift($matches);
                        $vals = array_map(function ($e) {
                            return '{' . $e . '}';
                        }, $keys);

                        $dummy_params = array_combine($keys, $vals);
                        $model->{$paramsField} = array_combine($keys, $matches);

                        break 1;
                    }
                }
            }

            $existing = Translation::find()->where(['en' => $en])->one();

            $translationId = 0;
            if ($existing === null || $existing === false) {
                // TODO: loop through all languages
                $en = \Yii::t($table, $en, $dummy_params, 'en');
                if ($en != '' && $en != null) {
                    $new = new Translation([
                        'en' => $en,
                        'de' => \Yii::t($table, $en, $dummy_params, 'de'),
                    ]);
                    $new->save();
                    $model->{$idField} = $new->id;
                    $translationId = $new->id;
                }
            } else {
                $model->{$idField} = $existing->id;
                $translationId = $existing->id;
            }

            // don't call events
            // update field_id and field_data fields
            Yii::$app->db->createCommand()->update($table, [
                $idField => $translationId,
                $dataField => $model->{$dataField}
            ], ['id' => $model->id])->execute();

            $i = $i + 1;
        }
        echo "\n";
    }

    private function migrateTableFieldDown($table, $field, $models, $nr)
    {
        $newField = $field . "_new";
        $paramsField = $field . "_params";

        $i = 1;
        foreach ($models as $model) {
            echo "Table " . $table . ": Migrating database record " . $i . "/" . $nr . " down\r";

            // don't call events
            $en = \Yii::t($table, $model->{$field}, $model->{$paramsField}, 'en');
            if ($en != '' && $en != null) {
                Yii::$app->db->createCommand()->update($table, [
                    $newField => $en,
                ], ['id' => $model->id])->execute();
            }
            $i = $i + 1;
        }
        echo "\n";
    }

}
