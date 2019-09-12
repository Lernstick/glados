<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Auth;
use app\models\AuthSearch;
use app\models\UserSearch;
use app\models\AuthLdapQueryForm;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\components\AccessRule;

class AuthController extends Controller
{

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
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
     * Lists all Auth models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AuthSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('/auth/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Auth model.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('/auth/' . $model->view, [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Auth model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Auth();
        $query_model = new AuthLdapQueryForm();
        $searchModel = new UserSearch();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else if (isset(\Yii::$app->request->post()['Auth']['class'])) { 
            $class = \Yii::$app->request->post()['Auth']['class'];
            $model = new $class();
            $query_model->auth_model = $model;

            if (Yii::$app->request->post('submit-button') !== null) {
                //submitted
            } else if (Yii::$app->request->post('test-auth-button') !== null) {
                // populate the $model->groups property with all AD groups found
                $model->load(Yii::$app->request->post());
                $query_model->load(Yii::$app->request->post());
                $query_model->auth_model = $model;
                $query_model->browse_ldap_for_groups();
            }

            return $this->render('create', [
                'model' => $model,
                'query_model' => $query_model,
                'searchModel' => $searchModel,
                'step' => 2,
            ]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'query_model' => $query_model,
                'searchModel' => $searchModel,
                'step' => 1,
            ]);
        }
    }

    /**
     * Updates an existing Auth model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $query_model = new AuthLdapQueryForm();
        $searchModel = new UserSearch();

        if (Yii::$app->request->post('submit-button') !== null) {
            //submitted
        } else if (Yii::$app->request->post('test-auth-button') !== null) {
            // populate the $model->groups property with all AD groups found
            $model->load(Yii::$app->request->post());
            $query_model->load(Yii::$app->request->post());
            $query_model->auth_model = $model;
            $query_model->browse_ldap_for_groups();
        } else {
            $query_model->auth_model = $model;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'query_model' => $query_model,
                'searchModel' => $searchModel,
            ]);
        }
    }

    /**
     * Finds the Auth model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Auth the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Auth::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
        }
    }

}