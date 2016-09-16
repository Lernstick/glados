<?php

namespace app\controllers;

use Yii;
use app\models\Screenshot;
use app\models\ScreenshotSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * ScreenshotController implements the CRUD actions for Screenshot model.
 */
class ScreenshotController extends Controller
{


    /**
     * Displays a single Screenshot model.
     * @param string $token
     * @param integer $date
     * @return mixed
     */
    public function actionView($token, $date)
    {

        if (($model = Screenshot::findOne($token, $date)) !== null){
            return \Yii::$app->response->sendFile($model->path, null, ['inline' => true]);
        }else{
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        
    }

}
