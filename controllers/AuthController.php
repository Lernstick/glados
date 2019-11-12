<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Auth;
use app\models\AuthSearch;
use app\models\AuthTestForm;
use app\models\UserSearch;
use app\models\AuthLdapQueryForm;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\components\AccessRule;
use yii\helpers\StringHelper;

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
     * @param string $waitDelete the id of an entry where deletion is in process
     * @return mixed
     */
    public function actionIndex($waitDelete = null)
    {
        if ($waitDelete !== null) {
            if (($model = $this->findModel($waitDelete, false)) !== null) {
                return $this->render('wait_for', ['id' => $waitDelete]);
            } else {
                return $this->redirect(['index']);
            }
        }

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
     * @param string $id
     * @param bool $wait if true the view will wait for the item to appear in the actual config
     * @param string $hash the md5 sum of the new content that the entry should posses, if set the view will wait until
     * the new content appears in the actual config
     * @return mixed
     */
    public function actionView($id, $wait = false, $hash = null)
    {
        if ($wait == true) {
            if (($model = $this->findModel($id, false)) !== null) {
                if ($hash !== null) {
                    if ($hash === md5(json_encode($model->fileConfig[$id]))) {
                        # maybe a redirect is not always the best option ???
                        return $this->redirect(['view', 'id' => $model->id]);
                        //return $this->render('/auth/' . $model->view, ['model' => $model]);
                    } else {
                        return $this->render('wait_for', ['id' => $id]);
                    }
                } else {
                    return $this->redirect(['view', 'id' => $model->id]);                    
                }
            } else {
                return $this->render('wait_for', ['id' => $id]);
            }
        }

        $model = $this->findModel($id);
        return $this->render('/auth/' . $model->view, ['model' => $model]);
    }

    /**
     * Creates a new Auth model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {

        $model = new Auth();
        $searchModel = new UserSearch();

        if (isset(\Yii::$app->request->post()['Auth']['class'])) {
            $class = \Yii::$app->request->post()['Auth']['class'];
            $model = new $class();

            if (Yii::$app->request->post('submit-button') !== null) {
                //submitted
                $model->scenario = $this->findScenario($model);
                if ($model->load(Yii::$app->request->post()) && $model->save()) {
                    return $this->redirect(['view',
                        'id' => $model->id,
                        'wait' => true,
                    ]);
                } else {
                    $model->scenario = $model::SCENARIO_DEFAULT;
                    return $this->render('create', [
                        'model' => $model,
                        'searchModel' => $searchModel,
                        'step' => 2,
                    ]);
                }
            } else if (Yii::$app->request->post('query-groups-button') !== null) {
                // populate the $model->groups property with all AD groups found
                $model->scenario = $model->class::SCENARIO_QUERY_GROUPS;
                $model->load(Yii::$app->request->post());
                $model->validate();
            }

            $model->scenario = $model::SCENARIO_DEFAULT;
            return $this->render('create', [
                'model' => $model,
                'searchModel' => $searchModel,
                'step' => 2,
            ]);
        } else {
            $model->scenario = Auth::SCENARIO_CREATE;
            return $this->render('create', [
                'model' => $model,
                'searchModel' => $searchModel,
                'step' => 1,
            ]);
        }
    }

    /**
     * Updates an existing Auth model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $searchModel = new UserSearch();

        if (Yii::$app->request->post('submit-button') !== null) {
            //submitted
            $model->scenario = $this->findScenario($model);
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                return $this->redirect(['view',
                    'id' => $model->id,
                    'wait' => true,
                    'hash' => md5(json_encode($model->getAttributes($model->activeAttributes()))),
                ]);
            } else {
                $model->scenario = $model::SCENARIO_DEFAULT;
                return $this->render('update', [
                    'model' => $model,
                    'searchModel' => $searchModel,
                ]);
            }
        } else if (Yii::$app->request->post('query-groups-button') !== null) {
            // populate the $model->groups property with all AD groups found
            $model->scenario = $model->class::SCENARIO_QUERY_GROUPS;
            $model->load(Yii::$app->request->post());
            $model->validate();
        }

        $model->scenario = $model::SCENARIO_DEFAULT;
        return $this->render('update', [
            'model' => $model,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Deletes an existing Auth model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        if ($this->findModel($id)->delete() !== false) {
            return $this->redirect(['index', 'waitDelete' => $id]);
        }
        return $this->redirect(['index']);
    }

    /**
     * Tests an existing Auth model.
     * @return mixed
     */
    public function actionTest()
    {

        $model = new AuthTestForm();
        $searchModel = new AuthSearch();

        $model->load(Yii::$app->request->post()) && $model->validate();

        return $this->render('test', [
            'model' => $model,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Migrates existing app\models\User models to app\models\UserAuth models 
     * associated to an existing Auth model.
     * @param string $id the Auth model
     * @return mixed
     */
    public function actionMigrate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->post('submit-button') !== null) {
            //submitted
            $model->scenario = $model->class::SCENARIO_MIGRATE;
            $model->load(Yii::$app->request->post()) && $model->validate();

            var_dump($model->errors);
            var_dump(false);die();
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('migrate', [
                    'model' => $model,
                ]);
            }
        } else if (Yii::$app->request->post('query-users-button') !== null) {
            // populate the $model->users property with all AD users found
            $model->scenario = $model->class::SCENARIO_QUERY_USERS;
            $model->load(Yii::$app->request->post());
            $model->validate();
        }

        return $this->render('migrate', [
            'model' => $model,
        ]);
    }

    /**
     * Finds the Auth model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown
     * or null is returned.
     * @param string $id
     * @param bool $lethal throw error or just return null in case of no result
     * @return Auth|null the loaded model or null if $lethal is not true
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, $lethal = true)
    {
        if (($model = Auth::findOne($id)) !== null) {
            return $model;
        } else {
            if ($lethal == true) {
                throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
            } else {
                return null;
            }
        }
    }

    /**
     * Finds the scenario.
     * @param Auth $model
     * @return string the scenario or SCENARIO_BIND_DIRECT
     */
    protected function findScenario($model)
    {
        //bind_direct or bind_byuser
        if (isset(Yii::$app->request->post($model->formName())['method'])) {
            $scenario = Yii::$app->request->post($model->formName())['method'];
        } else {
            $scenario = $model->class::SCENARIO_BIND_DIRECT;
        }
        return $scenario;
    }

}