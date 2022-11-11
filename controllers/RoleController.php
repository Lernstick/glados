<?php

namespace app\controllers;

use Yii;
use app\models\Role;
use app\models\RoleSearch;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;

/**
 * RoleController implements the CRUD actions for Role model.
 */
class RoleController extends BaseController
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
                'class' => \app\components\AccessControl::className(),
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
     * Lists all Role models.
     *
     * @param string $mode mode
     * @param string $attr attribute to make a list of
     * @param string $q query
     * @return mixed
     */
    public function actionIndex($mode = null, $attr = null, $q = null, $page = 1, $per_page = 10)
    {
        $searchModel = new RoleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);


        if ($mode === null) {
            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        } else if ($mode == 'list') {
            $out = [];
            if (!is_null($attr)) {
                if ($attr == 'name') {
                    $searchModel = new RoleSearch();
                    $out = $searchModel->selectList('CONCAT(description, " (", name, ")")', $q, $page, $per_page, 'name', false, 'type DESC,name ASC', true);
                } else if ($attr == 'role') {
                    $searchModel = new RoleSearch();
                    $searchModel->type = Role::TYPE;
                    $out = $searchModel->selectList('name', $q, $page, $per_page, null, true, null, false, ['type' => Role::TYPE]);
                }
            }
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $out;
        }
    } 

    /**
     * Displays a single Role model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $all = Yii::$app->authManager->getPermissions();

        $dataProvider = new ArrayDataProvider([
            'allModels' => $all,
            'pagination' => [
                'pageParam' => 'perm-page',
                'pageSizeParam' => 'perm-per-page',
                'pageSizeLimit' => [1, 100],
                'defaultPageSize' => 10,
            ],
        ]);

        if ($model->type == Role::TYPE) {

            return $this->render('view', [
                'model' => $model,
                'dataProvider' => $dataProvider,
            ]);

        } else {

            return $this->render('permission', [
                'model' => $model,
                'dataProvider' => $dataProvider,
            ]);

        }
    }

    /**
     * Creates a new Role model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Role();
        $all = array_merge(Yii::$app->authManager->getRoles(), Yii::$app->authManager->getPermissions());

        $dataProvider = new ArrayDataProvider([
            'allModels' => $all,
            'pagination' => false,
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->name]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'dataProvider' => $dataProvider,
            ]);
        }
    }

    /**
     * Updates an existing Role model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->type != Role::TYPE) {
            throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to view this page.'));
        }

        $all = array_merge(Yii::$app->authManager->getRoles(), Yii::$app->authManager->getPermissions());

        $dataProvider = new ArrayDataProvider([
            'allModels' => $all,
            'pagination' => false,
        ]);

        /**
         * This fix here is needed, because if nothing is selected in the CheckboxColumn,
         * then the Role.children element is absent in post(), resulting in no change.
         */
        $post = Yii::$app->request->post();
        if (!empty($post)) {
            $post['Role']['children'] = ArrayHelper::getValue($post, 'Role.children', []);
        }

        if ($model->load($post) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->name]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'dataProvider' => $dataProvider,
            ]);
        }
    }

    /**
     * Deletes an existing Role model.
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
     * Finds the Role model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Role the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Role::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }
}
