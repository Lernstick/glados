<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\Config;
use app\models\Stats;
use app\models\StatsSearch;
use app\components\AccessRule;
use yii\web\NotFoundHttpException;

/**
 * SystemController implements the CRUD actions for System model.
 */
class SystemController extends Controller
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
                        'roles' => ['rbac'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Displays System Config.
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
     * Displays System Statistics.
     *
     * @return mixed
     * @throws NotFoundHttpException if the config file cannot be found/parsed
     */
    public function actionStats()
    {

        $searchModel = new StatsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('stats', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

}
