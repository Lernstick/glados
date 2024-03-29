<?php

namespace app\controllers;

use Yii;
use app\models\Exam;
use app\models\ExamSearch;
use app\models\ExamSetting;
use app\models\ExamSettingAvail;
use app\models\ScreenCapture;
use app\models\forms\ExamForm;
use app\models\Ticket;
use app\models\TicketSearch;
use app\models\History;
use app\models\HistorySearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\data\ArrayDataProvider;
use kartik\mpdf\Pdf;
use \mPDF;
use yii\helpers\Url;


/**
 * ExamController implements the CRUD actions for Exam model.
 */
class ExamController extends BaseController
{

    /**
     * @inheritdoc
     */
    public $owner_actions = ['view', 'update', 'delete'];

    /**
     * @inheritdoc
     */
    public function getOwner_id()
    {
        $id = Yii::$app->request->get('id');
        if (($model = Exam::findOne($id)) !== null) {
            return $model->user_id;
        } else {
            return null;
        }
    }

    /*
     * @inheritdoc
     */
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

            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        } else if ($mode == 'list') {
            $out = [];
            if (!is_null($attr)) {
                if ($attr == 'name') {
                    $searchModel = new ExamSearch();
                    $out = $searchModel->selectList('name', $q, $page, $per_page);
                } else if ($attr == 'subject') {
                    $searchModel = new ExamSearch();
                    $out = $searchModel->selectList('subject', $q, $page, $per_page);
                } else if ($attr == 'resultExam') {
                    $searchModel = new ExamSearch();
                    $attr = 'CONCAT(name, " - ", subject)';
                    $out = $searchModel->selectList($attr, $q, $page, $per_page, 'id', false, 'name');
                } else if ($attr == 'settings') {
                    $searchModel = new ExamSettingAvail();
                    $out = $searchModel->selectList('name', $q, $page, $per_page, 'key', false);
                } else if ($attr == 'settings2') {
                    $params = Yii::$app->request->queryParams;
                    $searchModel = new ExamSettingAvail();
                    $dataProvider = $searchModel->search($params);
                    return $this->renderAjax('/exam/setting/index', [
                        'searchModel' => $searchModel,
                        'dataProvider' => $dataProvider,
                    ]);
                }
            }
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
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

        if ($mode === 'default') {
            
            $historySearchModel = new HistorySearch();
            $historyQueryParams = array_key_exists('HistorySearch', Yii::$app->request->queryParams)
                ? Yii::$app->request->queryParams['HistorySearch']
                : [];
            $historyParams = array_merge($historyQueryParams, [
                'table' => 'exam',
                'row' => $id,
            ]);
            $historyParams = ['HistorySearch' => $historyParams];
            $historyDataProvider = $historySearchModel->search($historyParams);
            $historyDataProvider->pagination->pageParam = 'hist-page';
            $historyDataProvider->pagination->pageSizeParam = 'hist-per-page';

            $settingsDataProvider = new ArrayDataProvider([
                'allModels' => $model->exam_setting,
            ]);

            return $this->render('view', [
                'model' => $model,
                'historySearchModel' => $historySearchModel,
                'historyDataProvider' => $historyDataProvider,
                'settingsDataProvider' => $settingsDataProvider,
            ]);

        } else if ($mode == "browse"){
            if ($type == "squashfs") {
                $file_list = Yii::$app->squashfs->set($model->file)->fileList;
            } else if ($type == "zip") {
                $file_list = Yii::$app->zip->set($model->file2)->fileList;
            } else {
                throw new NotFoundHttpException(\Yii::t('exams', 'The requested file has not a valid extension (zip, squashfs).'));
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
                    throw new NotFoundHttpException(\Yii::t('exams', 'The requested file does not exist.'));
                }
            } else if ($type == 'zip') {
                if (Yii::$app->file->set($model->file2)->exists) {
                    return \Yii::$app->response->sendFile($model->file2);
                } else {
                    throw new NotFoundHttpException(\Yii::t('exams', 'The requested file does not exist.'));
                }
            }
        } else if ($mode == "monitor"){

            return $this->redirect(['monitor/view', 'id' => $id]);

        } else if ($mode == "json"){
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                [
                    'name' => yii::$app->formatter->format(0, 'state'),
                    'y' => $model->openTickets,
                ],
                [
                    'name' => yii::$app->formatter->format(1, 'state'),
                    'y' => $model->runningTickets,
                ],
                [
                    'name' => yii::$app->formatter->format(2, 'state'),
                    'y' => $model->closedTickets,
                ],
                [
                    'name' => yii::$app->formatter->format(3, 'state'),
                    'y' => $model->submittedTickets,
                ],
                [
                    'name' => yii::$app->formatter->format(4, 'state'),
                    'y' => $model->ticketCount - $model->openTickets - $model->runningTickets - $model->closedTickets - $model->submittedTickets,
                ]
            ];

        } else if ($mode == 'report') {
            $models = Ticket::findAll(['exam_id' => $id, 'start' => null, 'end' => null]);
            if(!$models){
                Yii::$app->session->addFlash('danger', \Yii::t('exams', 'There are no Tickets to generate PDFs.'));
                return $this->redirect(['view', 'id' => $id]); 
            }

            $contents = [];

            foreach($models as $model){
                array_push($contents, $this->renderPartial('/ticket/report', [
                    'model' => $model,
                ]));
            }

            $content = implode('<pagebreak />', $contents);
            $title = \Yii::t('exams', 'Ticket for "{exam} - {subject}"', [
                'exam' => $model->exam->subject,
                'subject' => $model->exam->name
            ]);

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
        $model = new ExamForm();
        $model->exam = new Exam();
        $model->setAttributes(Yii::$app->request->post());

        if (is_array(Yii::$app->request->post('setting'))) {
            $setting = new ExamSetting([
                'key' => Yii::$app->request->post('setting')['key']
            ]);
            $setting->loadDefaultValue();
            return $this->renderAjax('setting/forms/value', [
                'id' => Yii::$app->request->post('setting')['id'],
                'form' => null,
                'setting' => $setting,
                'model' => $model,
            ]);
        }

        if (Yii::$app->request->post() && $model->save()) {
            return $this->redirect(['update', 'id' => $model->exam->id, 'step' => 2]);
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
        $model = new ExamForm();
        $model->exam = $this->findModel($id);
        $model->setAttributes(Yii::$app->request->post());

        if ($mode === 'default') {
            if ($model->exam->runningTickets != 0){
                Yii::$app->session->addFlash('danger', \Yii::t('exams', 'Exam edit is disabled while there {n,plural,=1{is one ticket} other{are # tickets}} in "Running" state.',
                    [ 'n' => $model->exam->runningTickets ]
                ));
                return $this->redirect(['view', 'id' => $model->exam->id]);
            }

            if (is_array(Yii::$app->request->post('setting'))) {
                $setting = new ExamSetting([
                    'key' => Yii::$app->request->post('setting')['key']
                ]);
                $setting->loadDefaultValue();
                return $this->renderAjax('setting/forms/value', [
                    'id' => Yii::$app->request->post('setting')['id'],
                    'form' => null,
                    'setting' => $setting,
                    'model' => $model,
                ]);
            }

            if (Yii::$app->request->post() && $model->save()) {
                return $this->redirect(['view', 'id' => $model->exam->id]);
            } else {

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
        } else if ($mode === 'upload') {
            $model = $model->exam;
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            $file = UploadedFile::getInstanceByName('file');
            $tmp = explode('.', $file->name);
            $extension = end($tmp);

            /**
             * @var array mapping of errorcodes and meaning
             * @see http://php.net/manual/en/features.file-upload.errors.php
             */
            $phpErrors = [
                0 => \Yii::t('fileUpload', 'There is no error, the file uploaded with success'),
                1 => \Yii::t('fileUpload', 'The uploaded file exceeds the upload_max_filesize directive in php.ini'),
                2 => \Yii::t('fileUpload', 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
                3 => \Yii::t('fileUpload', 'The uploaded file was only partially uploaded'),
                4 => \Yii::t('fileUpload', 'No file was uploaded'),
                6 => \Yii::t('fileUpload', 'Missing a temporary folder'),
                7 => \Yii::t('fileUpload', 'Failed to write file to disk.'),
                8 => \Yii::t('fileUpload', 'A PHP extension stopped the file upload.'),
            ];

            if ($file !== null) {
                $fileError = $phpErrors[$file->error] ? $phpErrors[$file->error] : \Yii::t('fileUpload', 'Unknown PHP File Upload Error: {error}', [ 'error' => $file->error ]);
            } else {
                $fileError = \Yii::t('fileUpload', 'Unknown File Upload Error');
            }

            if (!is_dir(\Yii::$app->params['uploadPath'])) {
                $fileError = \Yii::t('fileUpload', 'The upload directory ({dir}) does not exist.', [ 'dir'=> \Yii::$app->params['uploadPath'] ]);
                @unlink($file);
                return [ 'files' => [[
                    'name' => basename($file),
                    'error' => $fileError,
                ]]];                
            } else if (!is_writable(\Yii::$app->params['uploadPath'])) {
                $fileError = \Yii::t('fileUpload', 'The upload directory ({dir}) is not writable.', [ 'dir' => \Yii::$app->params['uploadPath'] ]);
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
            } else {
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
                Yii::$app->session->addFlash('danger', \Yii::t('exams', 'The exam cannot be deleted, since there are {n} tickets associated to it.', ['n' => $model->ticketCount]));

                return $this->redirect(['view', 'id' => $model->id]);
            }
            $model->delete();

            return $this->redirect(Yii::$app->session['examViewReturnURL']);
        } else if ($mode === 'file') {
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
        } else if ($mode === 'open_tickets') {

            $c = $model->delete_tickets(['state' => Ticket::STATE_OPEN]);
            if ($c !== 0) {
                Yii::$app->session->addFlash('danger', Yii::t('ticket', '{n} Open Tickets have been deleted successfully.', ['n' => $c]));
            } else {
                Yii::$app->session->addFlash('danger', Yii::t('ticket', 'There are no Open Tickets to delete.'));
            }

            return $this->redirect(['exam/view', 'id' => $id]);

        } else if ($mode === 'all_tickets') {

            $c = $model->delete_tickets([]);
            if ($c !== 0) {
                Yii::$app->session->addFlash('danger', Yii::t('ticket', '{n} Tickets have been deleted successfully.', ['n' => $c]));
            } else {
                Yii::$app->session->addFlash('danger', Yii::t('ticket', 'There are no Tickets to delete.'));
            }

            return $this->redirect(['exam/view', 'id' => $id]);
        }

    }

    /**
     * Finds the Exam model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Exam the loaded model
     * @throws NotFoundHttpException if the model cannot be found.
     */
    protected function findModel($id)
    {
        if (($model = Exam::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }
}
