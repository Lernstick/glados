<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "remote_execution".
 *
 * @property integer $id
 * @property string $cmd
 * @property string $host
 * @property string $env
 * @property float $requested_at
 */
class RemoteExecution extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'remote_execution';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cmd', 'host', 'env'], 'safe'],
            [['cmd', 'host', 'env'], 'required'],
            [['requested_at'], 'default', 'value' => microtime(true)],
        ];
    }

    /**
     * Insert the command into the database inside the queue and 
     * or update the existing entry if it already exists.
     */
    public function request()
    {
        $me = [
            'cmd' => $this->cmd,
            'env' => $this->env,
            'host' => $this->host,
        ];

        if (($model = RemoteExecution::findOne($me)) !== null) {
            $model->requested_at = microtime(true);
            return $model->save();
        } else {
            return $this->save();
        }
    }

}
