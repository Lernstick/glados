<?php

namespace app\controllers;

use Yii;
use app\models\Result;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\components\AccessRule;
use yii\web\UploadedFile;
use app\models\Ticket;
use app\models\TicketSearch;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;

/**
 * ResultController implements the CRUD actions for Result model.
 */
class ResultController extends Controller
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
                        'actions' => [
                            'view', // view the result
                            'download', // download the result

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
     * Displays a single Result model.
     * @param integer $token
     * @throws NotFoundHttpException if the model cannot be found
     * @return mixed
     */
    public function actionView($token = null)
    {

        if ($token === null){
            $model = new Ticket();
            $model->token = $token;

            return $this->render('_form', [
                'model' => $model,
            ]);
        }
        $model = Ticket::findOne(['token' => $token]);
        if (!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        } else {
            return $this->render('view', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Creates a new Result model.
     * If creation is successful, the browser will be redirected to the 'exam/view' page.
     * @return mixed
     */
    public function actionGenerate($exam_id = null, $path = '/')
    {

        if ($exam_id === null) {
            $params = Yii::$app->request->get('Ticket');
            $exam_id = (isset($params['exam_id']) && !empty($params['exam_id'])) ? $params['exam_id'] : null;

            $model = new Ticket();
            $searchModel = new TicketSearch();

            return $this->render('generate_s1', [
                'model' => $model,
                'searchModel' => $searchModel,
            ]);
        } else {
            $model = new Result([
                'scenario' => 'generate',
                'exam_id' => $exam_id,
            ]);

            $tickets = Ticket::find()->where([ 'and', ['exam_id' => $exam_id], [ 'not', [ "start" => null ] ], [ 'not', [ "end" => null ] ] ])->all();

            $selectedTickets = array_filter($tickets, function($t){
                return $t->result == null || !file_exists($t->result);
            });

            $tickets = ArrayHelper::map($tickets, 'id', 'resultName');
            $selectedTickets = ArrayHelper::map($selectedTickets, 'id', 'resultName');
            asort($tickets);

            if ($model->load(Yii::$app->request->post()) && $model->validate()) {

                $zipFile = $model->generateZip();

                if ($zipFile === null) {
                    Yii::$app->session->addFlash('danger', 'There are no closed or submitted Tickets to generate a ZIP-File.');
                    return $this->redirect(['exam/view', 'id' => $exam_id]); 
                } else if ($zipFile === false) {
                    throw new NotFoundHttpException('The ZIP-file could not be generated.');
                } else {
                    ignore_user_abort(true);
                    \Yii::$app->response->on(\app\components\customResponse::EVENT_AFTER_SEND, function($event) use ($zipFile) {
                        @unlink($zipFile);
                    }, $model);

                    return \Yii::$app->response->sendFile($zipFile, 'result.zip');                
                }

            } else {
                $ticket = new Ticket();
                $ticket->exam_id = $exam_id;

                return $this->render('generate', [
                    'model' => $model,
                    'tickets' => $tickets,
                    'selectedTickets' => $selectedTickets,
                    'ticket' => $ticket,
                ]);
            }
        }
    }

    /**
     * Downloads a single Result model.
     * @param integer $token
     * @throws NotFoundHttpException if the model cannot be found
     * @return mixed
     */
    public function actionDownload($token)
    {

        $model = Ticket::findOne(['token' => $token]);
        if (!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        } else {
            return \Yii::$app->response->sendFile($model->result);
        }

    }

    /**
     * Submit new Result models.
     * @return mixed
     */
    public function actionSubmit($mode = 'step1', $hash = null)
    {

        if ($hash === null){
            $model = new Result();
        } else {
            $model = Result::findOne($hash);
        }
        $model->scenario = Result::SCENARIO_SUBMIT;

        if ($mode === 'step1') {
            return $this->render('submit_s1', [
                'model' => $model,
            ]);

        } else if ($mode === 'step2'){

            $searchModel = new TicketSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
            $dataProvider->query = $dataProvider->query->andWhere(['token' => $model->tokens])->orderBy('test_taker');

            return $this->render('submit_s2', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'model' => $model,
            ]);

        } else if ($mode === 'step3'){

            $model->submit();

            return $this->redirect(['result/submit',
                'mode' => 'done',
                'hash' => $hash,
            ]);

        } else if ($mode === 'done'){

            $searchModel = new TicketSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
            $dataProvider->query = $dataProvider->query->andWhere(['token' => $model->tokens])->orderBy('test_taker');

            return $this->render('submit_done', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,                
                'model' => $model,
            ]);

        } else if ($mode === 'upload') {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $model->file = UploadedFile::getInstanceByName('file');

            /**
             * @var array mapping of errorcodes and meaning
             * @see http://php.net/manual/en/features.file-upload.errors.php
             */
            $phpErrors = [
                0 => 'There is no error, the file uploaded with success',
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                3 => 'The uploaded file was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.',
            ];

            if ($model->file !== null) {
                $fileError = $phpErrors[$model->file->error] ? $phpErrors[$model->file->error] : 'Unknown PHP File Upload Error: ' . $model->file->error;
            } else {
                $fileError = 'Unknown File Upload Error';
            }

            if (!is_dir(\Yii::$app->params['resultPath'])) {
                $fileError = 'The upload directory (' . \Yii::$app->params['resultPath'] . ') does not exist.';
                @unlink($model->file);
                return [ 'files' => [[
                    'name' => basename($model->file),
                    'error' => $fileError,
                ]]];                
            } else if (!is_writable(\Yii::$app->params['resultPath'])) {
                $fileError = 'The upload directory (' . \Yii::$app->params['resultPath'] . ') is not writable.';
                @unlink($model->file);
                return [ 'files' => [[
                    'name' => basename($model->file),
                    'error' => $fileError,
                ]]]; 
            }

            if ($model->file != null && $model->upload()) {
                $model->file = $model->filePath;

                if(!$model->validate()){
                    @unlink($model->file);
                    return [ 'files' => [[
                        'name' => basename($model->file),
                        'error' => $model->errors['id'][0],
                    ]]];
                }
            }else{
                @unlink($model->file);
                return [ 'files' => [[
                    'name' => basename($model->file),
                    'error' => $model->errors['file'][0] . ' ' . $fileError,
                ]]];
            }

            return [ 'files' => [[
                'name' => basename($model->file),
                'size' => filesize($model->file),
                'deleteUrl' => 'index.php?r=result/delete&mode=zip&file=' . basename($model->file), #TODO
                'deleteType' => 'POST'
            ]]];
        }

    }

}
