<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;

/**
 * This is the model class for the config.
 *
 * @property integer $port
 * @property string $type
 * @property string $avahiServiceFile
 * @property array $txtRecords
 */
class Config extends Model
{

    public $host;
    public $ip;
    public $port;
    public $avahiPort;
    public $avahiType;
    public $avahiServiceFile;
    public $avahiTxtRecords = [];
    public $params;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->params = \Yii::$app->params;
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'avahiPort' => \Yii::t('config', 'Avahi Port'),
            'avahiType' => \Yii::t('config', 'Avahi Protocol'),
            'avahiServiceFile' => \Yii::t('config', 'Avahi Service File'),
            'avahiTxtRecords' => \Yii::t('config', 'Avahi TXT Records'),
            'host' => \Yii::t('config', 'Host'),
            'ip' => \Yii::t('config', 'IP'),
            'port' => \Yii::t('config', 'Port'),
            'params' => \Yii::t('config', 'Options'),
        ];
    }


    /**
     * Return the Config model
     *
     * @return Config|null
     */
    public function findOne($config)
    {
        if (is_readable($config["avahiServiceFile"])) {
            $xml = simplexml_load_file($config["avahiServiceFile"]) or die("Error: Cannot create object");
            //print_r($xml);

            $me = new Config;
            $me->avahiServiceFile = $config["avahiServiceFile"];
            $me->avahiPort = (int) $xml->service->port;
            $me->avahiType = (string) $xml->service->type;
            $me->avahiTxtRecords = (array) $xml->{'service'}->{'txt-record'};
            $me->host = Yii::$app->request->serverName;
            $me->ip = gethostbyname(gethostname());
            $me->port = Yii::$app->request->serverPort;
            return $me;
        }
        return null;
    }

}
