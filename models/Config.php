<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * This is the model class for the config.
 *
 * @property integer $port
 * @property string $type
 */
class Config extends Model
{

    public $port;
    public $type;
    public $avahiServiceFile;
    public $txtRecords = [];

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'port' => 'Port',
            'type' => 'Protocol',
            'avahiServiceFile' => 'Avahi Service File',
            'txtRecords' => 'TXT Records',
        ];
    }


    /**
     * Return the Config model
     *
     * @return Config
     */
    public function findOne($config)
    {
        if (is_readable($config["avahiServiceFile"])) {
            $xml = simplexml_load_file($config["avahiServiceFile"]) or die("Error: Cannot create object");
            //print_r($xml);

            $me = new Config;
            $me->avahiServiceFile = $config["avahiServiceFile"];
            $me->port = (int) $xml->service->port;
            $me->type = (string) $xml->service->type;
            $me->txtRecords = (array) $xml->{'service'}->{'txt-record'};
            return $me;
        }
        return null;
    }

}
