<?php

namespace app\controllers;

use Yii;
use app\models\Howto;
use app\models\HowtoSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * HowtoController implements the CRUD actions for Exam model.
 */
class HowtoController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
            ],
        ];
    }

    /**
     * Lists all Howto models.
     * @return mixed
     */
    public function actionIndex()
    {

        $params = Yii::$app->request->queryParams;
        $searchModel = new HowtoSearch();
        $dataProvider = $searchModel->search($params);

        $dataProvider->pagination = array(
            'pageSize' => 10,
        );

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single image.
     * @param integer $id
     * @return mixed
     */
    public function actionImg($id)
    {
        if (strpos($id, '/') === false) {
            return \Yii::$app->response->sendFile(\Yii::$app->basePath . '/howtos/img/' . $id,
                basename($id),
                ['inline' => true]
            );
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Displays a single Howto model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id, $mode = null)
    {
        $model = $this->findModel($id);

        $params = Yii::$app->request->queryParams;
        $searchModel = new HowtoSearch();
        $dataProvider = $searchModel->search($params);
        $dataProvider->pagination = array(
            'pageSize' => 15,
        );

        if ($mode == 'inline') {
            $this->layout = 'client';
            $view = 'client-view';
        } else {
            $view = 'view';
        }

        if (filter_var($model->content, FILTER_VALIDATE_URL)) {
            return $this->redirect($model->content);
        } else {
            return $this->render($view, [
                'mode' => $mode,
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        }
    }

    /**
     * Finds the Howto model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Howto the loaded model
     * @throws NotFoundHttpException if the model cannot be found.
     */
    protected function findModel($id)
    {
        if (($model = Howto::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
