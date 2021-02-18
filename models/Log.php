<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use app\models\Ticket;

/**
 * This is the model class for the log directory.
 */
class Log extends Model
{

    /* log entry constants */
    const ENTRY_ERROR = 0;
    const ENTRY_WARNING = 1;
    const ENTRY_INFO = 2;

    /**
     * @property string the date in iso-8601 format
     */
    public $date;

    /**
     * @property string the type of logfile, can be download, backup, restore, fetch
     */
    public $type;

    /**
     * @property string the token of the associated ticket
     */
    public $token;

    /**
     * @property array list of words marking the line as error
     */
    public $error_words = [
        'error',
        'permission denied',
    ];

    /**
     * @property array list of words marking the line as warning
     */
    public $warning_words = [
        'warning',
    ];

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['date', 'type', 'token'], 'required'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'date' => Yii::t('log', 'Date'),
            'type' => Yii::t('log', 'Type'),
            'path' => Yii::t('log', 'Logfile Path'),
        ];
    }

    public function typeOfLine($line)
    {
        foreach($this->error_words as $w) {
            if (stripos($line, $w) !== false) return self::ENTRY_ERROR;
        }
        foreach($this->warning_words as $w) {
            if (stripos($line, $w) !== false) return self::ENTRY_WARNING;
        }
        return self::ENTRY_INFO;
    }

    /**
     * Getter for the path of the log file
     *
     * @return string
     */
    public function getPath()
    {
        return FileHelper::normalizePath(substitute('{runtime}/{type}.{token}.{date}.log', [
            'runtime' => Yii::getAlias('@runtime/logs/'),
            'type' => $this->type,
            'token' => $this->token,
            'date' => $this->date,
        ]));
    }

    /**
     * Getter for the contents of the log file
     *
     * @return array
     */
    public function getContents()
    {
        return gzfile($this->path);
    }


    /**
     * Returns all file paths matching the params
     *
     * @param array $params 
     * @return array
     */
    public static function findFiles($params)
    {

        $pattern = FileHelper::normalizePath(substitute('{type}.{token}.{date}.log', [
            'type' => array_key_exists('type', $params) && !empty($params['type']) ? $params['type'] : '*',
            'token' => array_key_exists('token', $params) && !empty($params['token']) ? $params['token'] : '*',
            'date' => array_key_exists('date', $params) && !empty($params['date']) ? $params['date'] : '*',
        ]));

        return FileHelper::findFiles(Yii::getAlias('@runtime/logs/'), [
            'only' => [$pattern],
            'recursive' => false,
        ]);
    }

    /**
     * Returns all Log models related to the token
     *
     * @param array $params 
     * @param string $token token
     * @return Log[]
     */
    public static function findAll($params)
    {
        $list = Log::findFiles($params);
        $models = [];
        foreach ($list as $path) {
            $matches = [];
            if (preg_match('/([\w]+)\.([\w]+)\.([^\.]+).log$/', $path, $matches) === 1) {
                $models[] = new Log([
                    'type' => $matches[1],
                    'token' => $matches[2],
                    'date' => $matches[3],
                ]);
            }
        }
        return $models;
    }

    /**
     * Return the Log model related to the token and the date
     *
     * @param string $token token
     * @param string $type log type (can be backup, restore, download, fetch, ...)
     * @param string $date date in iso-8601 format with seconds, example 2020-05-27T14:04:33+02:00
     * @return Log|null
     */
    public static function findOne($params)
    {
        $list = Log::findFiles($params);
        if (!empty($list)) {
            return new Log($params);
        } else {
            return null;
        }
    }
}
