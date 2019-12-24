<?php

namespace app\controllers;

use Yii;
use app\models\Setting;
use app\models\SettingSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\base\ViewNotFoundException;
use app\components\AccessRule;

/**
 * SettingController implements the CRUD actions for Setting model.
 */
class SettingController extends Controller
{
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
     * Lists all Setting models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SettingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Updates an existing Setting model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $contents = Yii::t('setting', 'There is no preview for this setting.');

        if ($this->preview_exists($model)) {
            if (is_array(Yii::$app->request->post('preview'))) {
                if (array_key_exists('value', Yii::$app->request->post('preview'))) {
                    $value = Yii::$app->request->post('preview')['value'];
                    $key = Yii::$app->request->post('preview')['key'];
                    $type = Setting::findByKey($key)->type;
                    Setting::set($key, $value, $type);
                }
            }

            $this->layout = 'preview';
            $contents = $this->render('previews/' . $model->key, [
                'model' => $model,
            ]);
            $this->layout = 'main';
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'contents' => $contents,
            ]);
        }
    }

    /**
     * If the preview view file exists
     *
     * @param Setting $model
     * @return bool
     */
    protected function preview_exists($model)
    {
        return is_file(Yii::getAlias('@app/views/setting/previews/' . $model->key) . '.php');
    }

    /**
     * Finds the Setting model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     * @return Setting the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Setting::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

}
