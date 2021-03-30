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
     * @property string the type of logfile, can be download, backup, restore, fetch, screen_capture
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
     * Locations to search for log files.
     * @return array
     */
    public static function locations()
    {
        return [
            [
                'dir'    => Yii::getAlias('@runtime/logs/'),
                'options' => [
                    'only' => ['{type}.{token}.{date}.log'],
                    'recursive' => false,
                ],
            ],
            [
                'dir' => FileHelper::normalizePath(\Yii::$app->params['scPath']),
                'options' => [
                    'only' => ['/{token}/{type}.log'],
                    'recursive' => true,
                ],
            ],
        ];
    }

    /**
     * TODO
     * @return array
     */
    public static function config()
    {
        return [
            'backup,restore,download,fetch,unlock' => [
                'path' => '{dir}/{type}.{token}.{date}.log',
                'date_fmt' => 'c', # 2004-02-12T15:19:21+00:00
                'findFiles' => [
                    'dir'    => Yii::getAlias('@runtime/logs/'),
                    'options' => [
                        'only' => ['{type}.{token}.{date}.log'],
                        'recursive' => false,
                    ],
                ],
                'pattern' => 'TODO',
            ],
            'screen_capture' => [
                'path' => '{dir}/{token}/{type}.log',
                'date_fmt' => 'c', # 2004-02-12T15:19:21+00:00
                'findFiles' => [
                    'dir' => FileHelper::normalizePath(\Yii::$app->params['scPath']),
                    'options' => [
                        'only' => ['/{token}/{type}.log'],
                        'recursive' => true,
                    ], // @TODO: filter the date somehow
                ],
                'pattern' => 'TODO',
            ],
            /*'keylogger' => [
                'path' => '{dir}/{token}/{type}{date}.key',
                'date_fmt' => 'U', # timestamp
                'findFiles' => [
                    'dir' => FileHelper::normalizePath(\Yii::$app->params['scPath']),
                    'options' => [
                        'only' => ['/{token}/{type}{date}.key'],
                        'recursive' => true,
                    ],
                ],
                'pattern' => 'TODO',
            ],*/
        ];
    }

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
        foreach ($this->config() as $keys => $config) {
            $keys = explode(',', $keys);
            foreach ($keys as $key) {
                if ($key == $this->type) {
                    return FileHelper::normalizePath(substitute($config['path'], [
                        'dir' => $config['findFiles']['dir'],
                        'type' => $this->type,
                        'token' => $this->token,
                        'date' => date($config['date_fmt'], strtotime($this->date)),
                    ]));
                }
            }
        }
        return null;
        /*return FileHelper::normalizePath(substitute('{dir}/{type}.{token}.{date}.log', [
            'dir' => Yii::getAlias('@runtime/logs/'),
            'type' => $this->type,
            'token' => $this->token,
            'date' => $this->date,
        ]));*/
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

        $sub = [
            'type' => array_key_exists('type', $params) && !empty($params['type']) ? $params['type'] : '*',
            'token' => array_key_exists('token', $params) && !empty($params['token']) ? $params['token'] : '*',
            'date' => array_key_exists('date', $params) && !empty($params['date']) ? $params['date'] : '*',
        ];

        $ret = [];

        foreach (self::config() as $types => $config) {
            $options = $config['findFiles']['options'];
            $dir = substitute($config['findFiles']['dir'], $sub);
            if (array_key_exists('only', $options)) {
                foreach ($options['only'] as $key => $value) {
                    $options['only'][$key] = substitute($options['only'][$key], $sub);
                }
            }
            $ret = array_merge($ret, FileHelper::findFiles($dir, $options));
        }

        return $ret;
        /*$pattern = FileHelper::normalizePath(substitute('{type}.{token}.{date}.log', [
            'type' => array_key_exists('type', $params) && !empty($params['type']) ? $params['type'] : '*',
            'token' => array_key_exists('token', $params) && !empty($params['token']) ? $params['token'] : '*',
            'date' => array_key_exists('date', $params) && !empty($params['date']) ? $params['date'] : '*',
        ]));

        return FileHelper::findFiles(Yii::getAlias('@runtime/logs/'), [
            'only' => [$pattern],
            'recursive' => false,
        ]);*/
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
            } else if (preg_match('/([\w]+)\/screen_capture.log$/', $path, $matches) === 1) {
                $models[] = new Log([
                    'token' => $matches[1],
                    'type' => 'screen_capture',
                    'date' => date ("c", filemtime($path)),
                ]);
            } else if (preg_match('/([\w]+)\/keylogger([0-9]+).key$/', $path, $matches) === 1) {
                $models[] = new Log([
                    'token' => $matches[1],
                    'type' => 'keylogger',
                    'date' => date ("c", $matches[2]),
                ]);
            }
        }
        return $models;
    }

    /**
     * Return the Log model related to the token and the date
     *
     * @param string $token token
     * @param string $type log type (can be backup, restore, download, fetch, unlock, ...)
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
