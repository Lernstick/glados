<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use app\models\Config;
use app\components\AccessRule;
use yii\web\NotFoundHttpException;

/**
 * ConfigController implements the CRUD actions for Config model.
 */
class ConfigController extends BaseController
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'info',  // public accessible information
                        ],
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['rbac'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Displays public accessible information about the server, such as its version.
     *
     * @return mixed
     */
    public function actionInfo()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return [
            "server_version" => \Yii::$app->version,
            "wants_client_version" => ">=1.0.12",
            "wants_lernstick_version" => ">=20200804", // 2020-08-04, notice without the dashes (from /usr/local/lernstick.html)
            "wants_lernstick_flavor" => "exam", // exam or standard
        ];
    }

    /**
     * Displays the system configuration.
     *
     * @return mixed
     * @throws NotFoundHttpException if the config file cannot be found/parsed
     */
    public function actionSystem()
    {

        $model = Config::findOne([
            'avahiServiceFile' => '/etc/avahi/services/glados.service'
        ]);

        if ($model !== null) {
            return $this->render('system', [
                'model' => $model,
            ]);
        } else {
            throw new NotFoundHttpException('The avahi service file (/etc/avahi/services/glados.service) could not be parsed.');
        }
    }

}
