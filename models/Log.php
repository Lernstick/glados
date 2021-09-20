<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
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

    private $_config;

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
     * Return the configurations for all logfiles.
     * 
     * ['key' => value]
     * * 'key' gives the types for this configuration, can be a comma separated list
     * * value is an array itself
     *   * path: path pattern of the logfile
     *   * date_fmt: format of the date within the path
     *   * search_date_fmt: format of the date to search in findFiles
     *   * from_date_fmt: how to parse the date, when creating the Datetime object with
     *     Datetime::createFromFormat(). null means create the object with new Datetime()
     *   * findFiles: array with dir and options
     *     * dir: the directory to give to findFiles() to search for logs
     *     * options: the options array for findFiles, can be used to restrict file matches
     *       via filenames
     * 
     * @return array
     */
    public static function configurations()
    {
        return [
            'backup,restore,download,prepare,fetch,unlock' => [
                'path' => '{dir}/{type}.{token}.{date}.log',
                'date_fmt' => 'c', # 2004-02-12T15:19:21+02:00
                'search_date_fmt' => 'Y-m-d*P', # 2004-02-12*+02:00
                'from_date_fmt' => null,
                'findFiles' => [
                    'dir'    => Yii::getAlias('@runtime/logs/'),
                    'options' => [
                        'only' => ['{type}.{token}.{date}*.log'],
                        'recursive' => false,
                    ],
                ],
            ],
            'screen_capture' => [
                'path' => '{dir}/{token}/{type}.log',
                'date_fmt' => 'c', # 2004-02-12T15:19:21+02:00
                'search_date_fmt' => 'c', # 2004-02-12T15:19:21+02:00
                'from_date_fmt' => null,
                'findFiles' => [
                    'dir' => FileHelper::normalizePath(\Yii::$app->params['scPath']),
                    'options' => [
                        'only' => ['/{token}/{type}.log'],
                        'recursive' => true,
                    ],
                ],
            ],
            'glados,error' => [
                'path' => '{dir}/{type}.{date}.log',
                'date_fmt' => 'Y-m-dO', # 2021-08-02+0200
                'search_date_fmt' => 'Y-m-dO',
                'from_date_fmt' => 'Y-m-dO',
                'findFiles' => [
                    'dir' => FileHelper::normalizePath(\Yii::$app->params['daemonLogFilePath']),
                    'options' => [
                        'only' => ['{type}.{date}.log'],
                        'recursive' => false,
                    ],
                ],
            ],
            'keylogger' => [
                'path' => '{dir}/{token}/{type}{date}.key',
                'date_fmt' => 'U', # timestamp
                'search_date_fmt' => 'U',
                'from_date_fmt' => 'U',
                'findFiles' => [
                    'dir' => FileHelper::normalizePath(\Yii::$app->params['scPath']),
                    'options' => [
                        'only' => ['/{token}/{type}*.key'],
                        'recursive' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array|null the configuration or null if not found.
     */
    public function getConfig() {
        if ($this->_config === null) {
            foreach ($this->configurations() as $keys => $config) {
                $keys = explode(',', $keys);
                foreach ($keys as $key) {
                    if ($key == $this->type) {
                        $this->_config = $config;
                    }
                }
            }
        }
        return $this->_config;
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
            'path' => Yii::t('log', 'Path'),
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
     * @return string|null
     */
    public function getPath()
    {
        if ($this->config !== null) {
            return FileHelper::normalizePath(substitute($this->config['path'], [
                'dir' => $this->config['findFiles']['dir'],
                'type' => $this->type,
                'token' => $this->token,
                'date' => $this->date,
            ]));
        }
        return null;
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
     * Getter for the filesize in bytes
     *
     * @return array
     */
    public function getSize()
    {
        return filesize($this->path);
    }

    /**
     * Returns the params in terms of wildcard patterns
     *
     * @param array $params 
     * @return array
     */
    public static function processParams($params)
    {
        return [
            'type' => array_key_exists('type', $params) && !empty($params['type']) ? $params['type'] : '*',
            'token' => array_key_exists('token', $params) && $params['token'] !== '' ? $params['token'] : '*',
            'date' => array_key_exists('date', $params) && !empty($params['date']) ? $params['date'] : '*',
        ];
    }

    /**
     * Returns whether the model matches the search pattern or not.
     *
     * @param array $params 
     * @return bool
     */
    public function matchExact($params)
    {
        $pparams = self::processParams($params);
        return $pparams['type'] == $this->type
            && $pparams['token'] == $this->token
            && $pparams['date'] == $this->date;
    }

    /**
     * Returns whether the model matches the search pattern or not.
     *
     * @param array $params 
     * @return bool
     */
    public function match($params)
    {
        $pparams = self::processParams($params);
        return $this->matchType($pparams['type'])
            && $this->matchToken($pparams['token'])
            && $this->matchDate($pparams['date']);
    }

    /**
     * Returns whether the model matches the date search pattern or not.
     *
     * @param string $date
     * @return bool
     */
    public function matchDate($date)
    {
        if ($date == "*") {
            return true;
        }
        $filterDateStart = \DateTime::createFromFormat('Y-m-d|', $date);
        $filterDateEnd = \DateTime::createFromFormat('Y-m-d|', $date);
        $filterDateEnd = $filterDateEnd->modify('+1 day');

        if ($this->config !== null) {
            if ($this->config['from_date_fmt'] === null) {
                $date = new \DateTime($this->date);
            } else {
                $date = \DateTime::createFromFormat($this->config['from_date_fmt'], $this->date);
            }
            return $filterDateStart <= $date && $date <= $filterDateEnd;
        }
        return false;
    }

    /**
     * Returns whether the model matches the token search pattern or not.
     *
     * @param string $token
     * @return bool
     */
    public function matchToken($token)
    {
        return StringHelper::matchWildcard($token, $this->token);
    }

    /**
     * Returns whether the model matches the type search pattern or not.
     *
     * @param string $type
     * @return bool
     */
    public function matchType($type)
    {
        return StringHelper::matchWildcard($type, $this->type);
    }

    /**
     * Returns all file paths matching the params
     *
     * @param array $params 
     * @return array
     */
    public static function findFiles($params, $matchWildcard = true)
    {
        $pparams = self::processParams($params);

        if ($pparams['date'] !== "*" && $matchWildcard) {
            $date = \DateTime::createFromFormat('Y-m-d|+', $pparams['date']);
        }

        $ret = [];
        foreach (self::configurations() as $types => $config) {
            $options = $config['findFiles']['options'];
            $dir = substitute($config['findFiles']['dir'], $pparams);
            if (array_key_exists('only', $options)) {
                foreach ($options['only'] as $key => $value) {
                    // format the date before substituting
                    if ($pparams['date'] !== "*" && $matchWildcard) {
                        $pparams['date'] = $date->format($config['search_date_fmt']);
                    }
                    $options['only'][$key] = substitute($options['only'][$key], $pparams);
                }
            }
            $ret = array_merge($ret, FileHelper::findFiles($dir, $options));
        }

        return $ret;
    }

    /**
     * Returns all Log models related to the token
     *
     * @param array $params 
     * @param string $limit limit
     * @return Log[]
     */
    public static function findAll($params, $limit = null)
    {
        $list = Log::findFiles($params);
        $models = [];
        $i = 0;
        foreach ($list as $path) {
            $matches = [];
            if (preg_match('/([\w]+)\.([\w]+)\.([^\.]+).log$/', $path, $matches) === 1) {
                $cfg = [
                    'type' => $matches[1],
                    'token' => $matches[2],
                    'date' => $matches[3],
                ];
            } else if (preg_match('/([\w]+)\/screen_capture.log$/', $path, $matches) === 1) {
                $cfg = [
                    'token' => $matches[1],
                    'type' => 'screen_capture',
                    'date' => date ("c", filemtime($path)),
                ];
            } else if (preg_match('/([\w]+)\/keylogger([0-9]+).key$/', $path, $matches) === 1) {
                $cfg = [
                    'token' => $matches[1],
                    'type' => 'keylogger',
                    'date' => $matches[2],
                ];
            } else if (preg_match('/([\w]+)\.([^\.]+).log$/', $path, $matches) === 1) {
                $cfg = [
                    'type' => $matches[1],
                    'token' => null,
                    'date' => $matches[2],
                ];
            } else {
                $cfg = null;
            }

            if ($cfg !== null) {
                $model = new Log($cfg);
                if ($model->match($params)) {
                    $models[] = $model;
                    $i++;
                    if ($limit !== null && $i >= $limit) {
                        break;
                    }
                }
            }
        }
        return $models;
    }

    /**
     * Return the Log model related to the token and the date
     *
     * @param string $token token
     * @param string $type log type (can be backup, restore, download, fetch, unlock, ...)
     * @param string $date date in whatever format is relevant for the type
     * @return Log|null
     */
    public static function findOne($params)
    {
        $list = Log::findFiles($params, false);
        $model = null;
        foreach ($list as $path) {
            $matches = [];
            if (preg_match('/([\w]+)\.([\w]+)\.([^\.]+).log$/', $path, $matches) === 1) {
                $cfg = [
                    'type' => $matches[1],
                    'token' => $matches[2],
                    'date' => $matches[3],
                ];
            } else if (preg_match('/([\w]+)\/screen_capture.log$/', $path, $matches) === 1) {
                $cfg = [
                    'token' => $matches[1],
                    'type' => 'screen_capture',
                    'date' => date ("c", filemtime($path)),
                ];
            } else if (preg_match('/([\w]+)\/keylogger([0-9]+).key$/', $path, $matches) === 1) {
                $cfg = [
                    'token' => $matches[1],
                    'type' => 'keylogger',
                    'date' => $matches[2],
                ];
            } else if (preg_match('/([\w]+)\.([^\.]+).log$/', $path, $matches) === 1) {
                $cfg = [
                    'type' => $matches[1],
                    'token' => null,
                    'date' => $matches[2],
                ];
            } else {
                $cfg = null;
            }

            if ($cfg !== null) {
                $model = new Log($cfg);
                if ($model->matchExact($params)) {
                    return $model;
                }
            }
        }
        return null;
    }
}
