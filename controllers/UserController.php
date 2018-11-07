<?php

namespace app\controllers;

use Yii;
use app\models\User;
use app\models\UserSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use app\components\AccessRule;
use yii\data\ArrayDataProvider;


/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
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
     * Lists all User models.
     *
     * @param string $mode mode
     * @param string $attr attribute to make a list of
     * @param string $q query
     * @return mixed
     */
    public function actionIndex($mode = null, $attr = null, $q = null)
    {
        if ($mode === null) {
            $searchModel = new UserSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        } else if ($mode == 'list') {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $out = [];
            if (!is_null($q) && !is_null($attr)) {
                $searchModel = new UserSearch();
                if ($attr == 'username') {
                    $out = $searchModel->selectList('username', $q);
                }
            }
            return $out;
        } 
    } 

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {

        $model = $this->findModel($id);
        $permissionDataProvider = new ArrayDataProvider([
                'allModels' => Yii::$app->authManager->getPermissionsByUser($model->id),
        ]);
        $permissionDataProvider->pagination->pageParam = 'perm-page';
        $permissionDataProvider->pagination->pageSize = 10;

        return $this->render('view', [
            'model' => $model,
            'permissionDataProvider' => $permissionDataProvider,
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User(['scenario' => 'create']);
        $searchModel = new UserSearch();

//        $auth = Yii::$app->authManager;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'searchModel' => $searchModel,
            ]);
        }
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = User::SCENARIO_UPDATE;
        $searchModel = new UserSearch();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'searchModel' => $searchModel,
            ]);
        }
    }

    /**
     * Resets a password for an existing User model.
     * If reset is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionResetPassword($id)
    {
        $model = $this->findModel($id);
        $model->scenario = User::SCENARIO_PASSWORD_RESET;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('reset-password', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {

        if (($model = User::findOne($id)) !== null) {
            if ($this->checkRbac($model)) {
                return $model;
            }
        }
        throw new NotFoundHttpException('The requested page does not exist.');

        /*if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }*/
    }

    protected function checkRbac($model)
    {
        $r = \Yii::$app->controller->id . '/' . \Yii::$app->controller->action->id;
        if(Yii::$app->user->can($r . '/all') || $model->id == Yii::$app->user->id){
            return true;
        }else{
            throw new ForbiddenHttpException('You are not allowed to ' . \Yii::$app->controller->action->id . 
                    ' this ' . \Yii::$app->controller->id . '.');
            return false;
        }
    }

}
