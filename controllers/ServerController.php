<?php

namespace app\controllers;

use Yii;
use app\models\Config;
use app\models\ServerStatus;
use yii\web\NotFoundHttpException;

/**
 * ServerController implements the CRUD actions for Config model.
 */
class ServerController extends BaseController
{

    /**
     * Displays the server configuration.
     *
     * @return mixed
     * @throws NotFoundHttpException if the config file cannot be found/parsed
     */
    public function actionConfig()
    {

        $model = Config::findOne([
            'avahiServiceFile' => '/etc/avahi/services/glados.service'
        ]);

        if ($model !== null) {
            return $this->render('config', [
                'model' => $model,
            ]);
        } else {
            throw new NotFoundHttpException('The avahi service file (/etc/avahi/services/glados.service) could not be parsed.');
        }
    }

    /**
     * Displays the server status.
     *
     * @return mixed
     * @throws NotFoundHttpException if the config file cannot be found/parsed
     */
    public function actionStatus()
    {
        $model = ServerStatus::find();
        $model->load(Yii::$app->request->get());

        return $this->render('status', [
            'model' => $model,
        ]);
    }

}
