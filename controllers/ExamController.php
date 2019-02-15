<?php

namespace app\controllers;

use Yii;
use app\models\Exam;
use app\models\ExamSearch;
use app\models\Ticket;
use app\models\TicketSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use app\components\AccessRule;
use yii\web\UploadedFile;
use yii\data\ArrayDataProvider;
use kartik\mpdf\Pdf;
use \mPDF;
use yii\helpers\Url;


/**
 * ExamController implements the CRUD actions for Exam model.
 */
class ExamController extends Controller
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
     * Lists all Exam models.
     *
     * @param string $mode mode
     * @param string $attr attribute to make a list of
     * @param string $q query
     * @return mixed
     */
    public function actionIndex($mode = null, $attr = null, $q = null, $page = 1, $per_page = 10)
    {

        if ($mode === null) {
            Yii::$app->session['examViewReturnURL'] = Yii::$app->request->Url;
            $params = Yii::$app->request->queryParams;

            $searchModel = new ExamSearch();
            $dataProvider = $searchModel->search($params);
            $session = Yii::$app->session;

            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'session' => $session,
            ]);
        } else if ($mode == 'list') {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $out = [];
            if (!is_null($attr)) {
                $searchModel = new ExamSearch();
                if ($attr == 'name') {
                    $out = $searchModel->selectList('name', $q, $page, $per_page);
                } else if ($attr == 'subject') {
                    $out = $searchModel->selectList('subject', $q, $page, $per_page);
                } else if ($attr == 'resultExam') {
                    $attr = 'CONCAT(name, " - ", subject)';
                    $out = $searchModel->selectList($attr, $q, $page, $per_page, 'id', false, 'name');
                }
            }
            return $out;
        } 
    }

    /**
     * Displays a single Exam model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id, $mode = 'default', $type = 'squashfs')
    {

        $model = $this->findModel($id);
        $session = Yii::$app->session;

        if ($mode === 'default') {

            $models = preg_split("/\r\n|\n|\r/", $model->{"url_whitelist"}, null, PREG_SPLIT_NO_EMPTY);
            $models = array_merge($models, preg_split("/\r\n|\n|\r/", $model->{"sq_url_whitelist"}, null, PREG_SPLIT_NO_EMPTY));
            $urlWhitelistDataProvider = new ArrayDataProvider([
                'allModels' => $models,
            ]);
            $urlWhitelistDataProvider->pagination->pageParam = 'url-page';
            $urlWhitelistDataProvider->pagination->pageSize = 10;            

            return $this->render('view', [
                'model' => $model,
                'urlWhitelistDataProvider' => $urlWhitelistDataProvider,
                'session' => $session,
            ]);

        } else if ($mode == "browse"){
            if ($type == "squashfs") {
                $file_list = Yii::$app->squashfs->set($model->file)->fileList;
            } else if ($type == "zip") {
                $file_list = Yii::$app->zip->set($model->file2)->fileList;
            } else {
                throw new NotFoundHttpException('The requested file has not a valid extension (zip, squashfs).');
            }

            $dataProvider = new ArrayDataProvider([
                'allModels' => $file_list,
            ]);

            return $this->render('_view-file-details', [
                'model' => $model,
                'type' => $type,
                'dataProvider' => $dataProvider,
            ]);
        } else if ($mode == "file"){
            if ($type == 'squashfs') {
                if (Yii::$app->file->set($model->file)->exists) {
                    return \Yii::$app->response->sendFile($model->file);
                } else {
                    throw new NotFoundHttpException('The requested file does not exist.');
                }
            } else if ($type == 'zip') {
                if (Yii::$app->file->set($model->file2)->exists) {
                    return \Yii::$app->response->sendFile($model->file2);
                } else {
                    throw new NotFoundHttpException('The requested file does not exist.');
                }
            }
        } else if ($mode == "monitor"){
            $params["TicketSearch"]["exam_id"] = $model->id;
            $params["sort"] = 'token';

            $searchModel = new TicketSearch();
            $dataProvider = $searchModel->search($params);
            $dataProvider->pagination->pageParam = 'mon-page';
            $dataProvider->pagination->pageSize = 9;

            return $this->render('monitor', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'session' => $session,
            ]);

        } else if ($mode == "json"){
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                [
                    'name' => yii::$app->formatter->format(0, 'state'),
                    'y' => $model->openTicketCount,
                ],
                [
                    'name' => yii::$app->formatter->format(1, 'state'),
                    'y' => $model->runningTicketCount,
                ],
                [
                    'name' => yii::$app->formatter->format(2, 'state'),
                    'y' => $model->closedTicketCount,
                ],
                [
                    'name' => yii::$app->formatter->format(3, 'state'),
                    'y' => $model->submittedTicketCount,
                ],
                [
                    'name' => yii::$app->formatter->format(4, 'state'),
                    'y' => $model->ticketCount - $model->openTicketCount - $model->runningTicketCount - $model->closedTicketCount - $model->submittedTicketCount,
                ]
            ];

        } else if ($mode == 'report') {
            $models = Ticket::findAll(['exam_id' => $id, 'start' => null, 'end' => null]);
            if(!$models){
                Yii::$app->session->addFlash('danger', 'There are no Tickets to generate PDFs.');
                return $this->redirect(['view', 'id' => $id]); 
            }

            $contents = [];

            foreach($models as $model){
                array_push($contents, $this->renderPartial('/ticket/report', [
                    'model' => $model,
                ]));
            }

            $content = implode('<pagebreak />', $contents);
            $title = 'Ticket for "' . $model->exam->subject . ' - ' . $model->exam->name . '"';

            $pdf = new Pdf([
                'mode' => Pdf::MODE_UTF8,
                'format' => Pdf::FORMAT_A4,
                'orientation' => Pdf::ORIENT_PORTRAIT,
                'filename' => 'Tickets.pdf',
                'destination' => Pdf::DEST_BROWSER,
                'content' => $content,
                'options' => ['title' => $title],
                'methods' => [
                    'SetHeader' => [$title],
                    'SetFooter' => ['{PAGENO}'],
                ]
            ]);

            return $pdf->render();

        }
    }


    /**
     * Creates a new Exam model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Exam();

        if ($model->load(\Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['update', 'id' => $model->id, 'step' => 2]);
        } else {
            return $this->render('create', [
                'model' => $model,
                'step' => 1,
            ]);
        }
    }

    /**
     * Updates an existing Exam model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @param string $mode default or file
     * @param integer $step 0 means a normal edit of the exam, 1 is step 1 in create exam, 2 is step 2 in create exam
     * @return mixed
     */
    public function actionUpdate($id, $mode = 'default', $step = 0)
    {
        $model = $this->findModel($id);

        if ($mode === 'default') {
            if ($model->runningTicketCount != 0){
                Yii::$app->session->addFlash('danger', 'Exam edit is disabled while there are ' . $model->runningTicketCount . ' tickets in "Running" state.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            if ($model->load(Yii::$app->request->post()) && $model->save()){
                return $this->redirect(['view', 'id' => $model->id]);
            }else{
                if ($step == 0) {
                    return $this->render('update', [
                        'model' => $model,
                        'step' => $step,
                    ]);
                } else if ($step == 2) {
                    return $this->render('create_s2', [
                        'model' => $model,
                        'step' => $step,
                    ]);
                }
            }
        }else if ($mode === 'upload') {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            $file = UploadedFile::getInstanceByName('file');
            $extension = end(explode('.', $file->name));

            /*if ($pathinfo['extension'] == 'zip') {
                $model->file2 = $file;
            } else if ($pathinfo['extension'] == 'squashfs') {
                $model->file = $file;
            }*/
            //$model->file = UploadedFile::getInstanceByName('file');

            //var_dump(UploadedFile::getInstanceByName('file'));
            //die();

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

            if ($file !== null) {
                $fileError = $phpErrors[$file->error] ? $phpErrors[$file->error] : 'Unknown PHP File Upload Error: ' . $file->error;
            } else {
                $fileError = 'Unknown File Upload Error';
            }

            if (!is_dir(\Yii::$app->params['uploadPath'])) {
                $fileError = 'The upload directory (' . \Yii::$app->params['uploadPath'] . ') does not exist.';
                @unlink($file);
                return [ 'files' => [[
                    'name' => basename($file),
                    'error' => $fileError,
                ]]];                
            } else if (!is_writable(\Yii::$app->params['uploadPath'])) {
                $fileError = 'The upload directory (' . \Yii::$app->params['uploadPath'] . ') is not writable.';
                @unlink($file);
                return [ 'files' => [[
                    'name' => basename($file),
                    'error' => $fileError,
                ]]]; 
            }

            if ($file != null && $model->upload($file)) {

                $file = $model->filePath;
                if ($extension == 'zip') {
                    $model->file2 = $model->filePath;
                } else if ($extension == 'squashfs') {
                    $model->file = $model->filePath;
                }

                if(!$model->save()){
                    @unlink($file);
                    return [ 'files' => [[
                        'name' => basename($file),
                        'error' => $model->errors['id'][0],
                    ]]];
                }
            }else{
                @unlink($file);
                return [ 'files' => [[
                    'name' => basename($file),
                    'error' => $model->errors['file'][0] . ' ' . $fileError,
                ]]];
            }

            return [ 'files' => [[
                'name' => basename($file),
                'size' => filesize($file),
                'deleteUrl' => Url::to(['delete', 'id' => $model->id, 'mode' => 'file', 'type' => $extension]),
                'deleteType' => 'POST'
            ]]];

        }
    }

    /**
     * Deletes an existing Exam model or a squashfs file.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id, $mode = 'exam', $type = null)
    {

        $model = $this->findModel($id);

        if ($mode === 'exam') {
            if($model->ticketCount != 0){
                Yii::$app->session->addFlash('danger', 'The exam cannot be deleted, since there are ' . $model->ticketCount . ' tickets associated to it.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            $model->delete();
            Yii::$app->session->addFlash('danger', 'The Exam has been deleted successfully.');

            return $this->redirect(Yii::$app->session['examViewReturnURL']);
        }else if ($mode === 'file') {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            if ($type == 'squashfs') {
                return [ 'files' => [[
                    basename($model->file) => $model->deleteFile('squashfs'),
                ]]];
            } else if ($type == 'zip') {
                return [ 'files' => [[
                    basename($model->file2) => $model->deleteFile('zip'),
                ]]];
            }
        }

    }


    /**
     * Finds the Exam model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Exam the loaded model
     * @throws NotFoundHttpException if the model cannot be found.
     * @throws ForbiddenHttpException if access control failed.
     */
    protected function findModel($id)
    {
        if (($model = Exam::findOne($id)) !== null) {
            $r = \Yii::$app->controller->id . '/' . \Yii::$app->controller->action->id;
            if(Yii::$app->user->can($r . '/all') || $model->user_id == Yii::$app->user->id){
                return $model;
            }else{
                throw new ForbiddenHttpException('You are not allowed to ' . \Yii::$app->controller->action->id . 
                    ' this ' . \Yii::$app->controller->id . '.');
            }
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
