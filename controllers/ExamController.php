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
     * @return mixed
     */
    public function actionIndex()
    {

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
    }

    /**
     * Displays a single Exam model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id, $mode = 'default')
    {

        $model = $this->findModel($id);
        $session = Yii::$app->session;

        if ($mode === 'default') {

            $urlWhitelistDataProvider = new ArrayDataProvider([
                'allModels' => preg_split("/\r\n|\n|\r/", $model->{"url_whitelist"}, null, PREG_SPLIT_NO_EMPTY),
            ]);
            $urlWhitelistDataProvider->pagination->pageParam = 'url-page';
            $urlWhitelistDataProvider->pagination->pageSize = 10;            

            return $this->render('view', [
                'model' => $model,
                'urlWhitelistDataProvider' => $urlWhitelistDataProvider,
                'session' => $session,
            ]);
        }else if ($mode == "squashfs"){
            $file_list = Yii::$app->squashfs->set($model->file)->fileList;

            $dataProvider = new ArrayDataProvider([
                'allModels' => $file_list,
            ]);

            return $this->render('_view-file-details', [
                'dataProvider' => $dataProvider,
            ]);
        }else if ($mode == "monitor"){
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
        }else if ($mode == "json"){
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
        }else if ($mode == 'report') {
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
            $title = 'Ticket für Prüfung "' . $model->exam->subject . ' - ' . $model->exam->name . '"';

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
            return $this->redirect(['update', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }


    /**
     * Updates an existing Exam model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id, $mode = 'default')
    {
        $model = $this->findModel($id);

        if ($mode === 'default') {
            if ($model->runningTicketCount != 0){
                Yii::$app->session->addFlash('danger', 'Exam update is disabled while there are ' . $model->runningTicketCount . ' tickets in "Running" state.');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            if ($model->load(Yii::$app->request->post()) && $model->save()){
                return $this->redirect(['view', 'id' => $model->id]);
            }else{
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }else if ($mode === 'upload') {
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

            if (!is_dir(\Yii::$app->params['uploadDir'])) {
                $fileError = 'The upload directory does not exist.';
                @unlink($model->file);
                return [ 'files' => [[
                    'name' => basename($model->file),
                    'error' => $fileError,
                ]]];                
            } else if (!is_writable(\Yii::$app->params['uploadDir'])) {
                $fileError = 'The upload directory is not writable.';
                @unlink($model->file);
                return [ 'files' => [[
                    'name' => basename($model->file),
                    'error' => $fileError,
                ]]]; 
            }

            if ($model->file != null && $model->upload()) {
                $model->file = $model->filePath;

                if(!$model->save()){
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
                'deleteUrl' => 'index.php?r=exam/delete&id=' . $model->id . '&mode=squashfs&file=' . basename($model->file),
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
    public function actionDelete($id, $mode = 'exam')
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
        }else if ($mode === 'squashfs') {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [ 'files' => [[
                basename($model->file) => $model->deleteFile(),
            ]]];
        }

    }

    /**
     * Finds the Exam model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Exam the loaded model
     * @throws NotFoundHttpException if the model cannot be found.
     * @throws ForbiddenHttpException if the access control failed.
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
