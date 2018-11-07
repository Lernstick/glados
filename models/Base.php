<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the base model class.
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
class Base extends \yii\db\ActiveRecord
{

    /**
     * Lists an attribute
     * @param attr attribute to list
     * @param q query
     * @param id which attribute should be the id
     * @param showQuery whether the query itself should be shown in the 
     *                  output list.
     *
     * @return TicketQuery
     */
    public function selectList($attr, $q, $id = null, $showQuery = true)
    {
        $id = is_null($id) ? $attr : $id;
        $query = $this->find();
        $query->select([$id . ' as id', $attr . ' AS text'])
            ->distinct()
            ->where(['like', $attr, $q]);

        if ($this->tableName() != "user") {
            Yii::$app->user->can($this->tableName() . '/index/all') ?: $query->own();
        }

        $out = ['results' => []];
        if ($showQuery === true) {
            $out = ['results' => [
                0 => ['id' => $q, 'text' => $q]
            ]];
        }

        $command = $query->limit(20)->createCommand();
        $data = $command->queryAll();
        $out['results'] = array_merge($out['results'], array_values($data));
        return $out;
    }

}
