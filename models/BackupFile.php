<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\components\ElasticsearchBehavior;

/**
 * This is the model class for the backup directory.
 *
 * @property string $path
 * @property array $mimetype
 * @property array $contents
 *
 * @property Ticket $ticket
 */
class BackupFile extends Model
{
    public $path;
    public $mimetype;
    public $content;
    public $ticket_id;
    public $date;

    public $id;

    /**
     * @inheritdoc 
     */
    public function behaviors()
    {
        return [
            'ElasticsearchBehavior' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => 'file',
                // what the attributes mean
                'fields' => [
                    'path',
                    'mimetype',
                    'content',
                    'date',
                    'ticket' => function($m) { return $m->ticket_id; },
                ],
                // mapping of elasticsearch
                'properties' => [
                    'path'     => ['type' => 'text'],
                    'mimetype' => ['type' => 'text'],
                    'content'  => ['type' => 'text'],
                    'date'     => ['type' => 'text'], // @todo: change back to date
                    'ticket'   => ['type' => 'integer'],
                ]
            ],
        ];
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['path'], 'required'],
        ];
    }


}
