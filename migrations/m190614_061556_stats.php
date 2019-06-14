<?php

use yii\db\Migration;
use app\models\Ticket;


/**
 * Class m190614_061556_stats
 */
class m190614_061556_stats extends Migration
{

    public $statsTable = 'stats';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // create the stats table
        if ($this->db->schema->getTableSchema($this->statsTable, true) === null) {
            $this->createTable($this->statsTable, [
                'id' => $this->primaryKey(),
                'date' => $this->timestamp()->null()->defaultExpression('CURRENT_TIMESTAMP'),
                'key' => $this->text(),
                'value' => $this->text(),
                'type' => $this->text(),
            ], $this->tableOptions);
        }

        $completed_exams = intval(Ticket::find()
            ->where(['not', ['start' => null]])
            ->andWhere(['not', ['end' => null]])
            ->count()
        );

        $total_duration = intval(Ticket::find()
            // only count exams shorter or equal than 8 hours
            ->where('TIMESTAMPDIFF(SECOND, `start`, `end`) <= :upper', [':upper' => 28800])

            // only count exams longer or equal than 15 minutes
            ->andWhere('TIMESTAMPDIFF(SECOND, `start`, `end`) >= :lower', [':lower' => 900])

            // sum over all these
            ->sum('TIMESTAMPDIFF(
                SECOND,
                `start`,
                `end`
            )')
        );

        $this->insert($this->statsTable, [
            'date' => null,
            'key' => 'completed_exams',
            'value' => $completed_exams,
            'type' => 'int',
        ]);

        $this->insert($this->statsTable, [
            'date' => null,
            'key' => 'total_duration',
            'value' => $total_duration,
            'type' => 'int',
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->schema->getTableSchema($this->statsTable, true) !== null) {
            $this->dropTable($this->statsTable);
        }
    }
}
