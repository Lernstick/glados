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
            'avahiPort' => 'Avahi Port',
            'avahiType' => 'Avahi Protocol',
            'avahiServiceFile' => 'Avahi Service File',
            'avahiTxtRecords' => 'Avahi TXT Records',
            'params' => 'Options',
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
