<?php

namespace app\controllers;

use Yii;
use app\models\Ticket;
use app\models\TicketSearch;
use app\models\Activity;
use app\models\ActivitySearch;
use app\models\Backup;
use app\models\BackupSearch;
use app\models\Restore;
use app\models\RestoreSearch;
use app\models\Screenshot;
use app\models\ScreenshotSearch;
use app\models\Exam;
use app\models\EventItem;
use app\models\Daemon;
use app\models\DaemonSearch;
use app\models\RdiffFileSystem;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;
use yii\filters\VerbFilter;
use yii\db\Expression;
use kartik\mpdf\Pdf;
use \mPDF;
use app\components\customResponse;
use app\components\AccessRule;
use yii\data\ArrayDataProvider;


/**
 * TicketController implements the CRUD actions for Ticket model.
 */
class TicketController extends Controller
{

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
                'class' => \yii\filters\AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'download', // download/start exam
                            'md5',      // verify exam file
                            'config',   // retrieve exam config
                            'ssh-key',  // get public server ssh key
                            'notify',   // notify a new client status
                            'finish',   // finish exam
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
     * Lists all Ticket models.
     * @return mixed
     */
    public function actionIndex()
    {

        #TODO not user->id, because not set.
        //$current_user = Yii::$app->user->id;
        Yii::$app->session['ticketViewReturnURL'] = Yii::$app->request->Url;

        $searchModel = new TicketSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $session = Yii::$app->session;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'session' => $session,
        ]);
    }

    //TODO: rbac
    /**
     * Displays a single Ticket model.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionView($id, $mode = 'default', $online = -1, $path = '/', $showDotFiles = false, $date = null)
    {

        $model = $this->findModel($id);
        if ($mode == 'default') {
            //$model = $this->findModel($id);
            $session = Yii::$app->session;

            $lastState = $session['ticketLastState' . $model->id];
            if(isset($lastState) && $lastState != $model->state){
                $session->addFlash('info', 'The Ticket state has changed from ' . 
                    Yii::$app->formatter->format($lastState, 'state') . ' to ' . 
                    Yii::$app->formatter->format($model->state, 'state'));
            }
            $session['ticketLastState' . $model->id] = $model->state;

            $activitySearchModel = new ActivitySearch();
            $activityDataProvider = $activitySearchModel->search(['ActivitySearch' => ['ticket_id' => $id] ]);
            $activityDataProvider->pagination->pageParam = 'act-page';
            $activityDataProvider->pagination->pageSize = 10;

            $backupSearchModel = new BackupSearch();
            $backupDataProvider = $backupSearchModel->search($model->token);
            $backupDataProvider->pagination->pageParam = 'back-page';
            $backupDataProvider->pagination->pageSize = 5;

            $screenshotSearchModel = new ScreenshotSearch();
            $screenshotDataProvider = $screenshotSearchModel->search($model->token);
            $screenshotDataProvider->pagination->pageParam = 'screen-page';
            $screenshotDataProvider->pagination->pageSize = 12;

            $restoreSearchModel = new RestoreSearch();
            $restoreDataProvider = $restoreSearchModel->search(['RestoreSearch' => ['ticket_id' => $id] ]);
            $restoreDataProvider->pagination->pageParam = 'rest-page';
            $restoreDataProvider->pagination->pageSize = 5;

            $options = [
                'showDotFiles' => boolval($showDotFiles),
            ];

            $fs = new RdiffFileSystem([
                'root' => $model->exam->backup_path,
                'location' => \Yii::getAlias('@app/backups/' . $model->token),
                'restoreUser' => 'root',
                'restoreHost' => $model->ip,
                'options' => $options,
            ]);

            if ($date == null) {
                $date = ($model->state == Ticket::STATE_CLOSED || $model->state == Ticket::STATE_SUBMITTED) ? $fs->newestBackupVersion : 'all';
            }

            if (file_exists(\Yii::getAlias('@app/backups/' . $model->token))) {
                $models = $fs->slash($path)->versionAt($date)->contents;
                $versions = $fs->slash($path)->versions;
                //array_unshift($versions , 'now');
                array_unshift($versions , 'all');
            } else {
                $models = [];
                $versions = [];
            }

            $ItemsDataProvider = new ArrayDataProvider([
                'allModels' => $models,
            ]);

            $ItemsDataProvider->pagination->pageParam = 'browse-page';
            $ItemsDataProvider->pagination->pageSize = 20;

            $VersionsDataProvider = new ArrayDataProvider([
                'allModels' => $versions,
            ]);

            $VersionsDataProvider->pagination->pageParam = 'vers-page';
            $VersionsDataProvider->pagination->pageSize = 10;

            return $this->render('view', [
                'model' => $model,
                'online' => $online,
                'session' => $session,
                'activitySearchModel' => $activitySearchModel,
                'activityDataProvider' => $activityDataProvider,
                'backupSearchModel' => $backupSearchModel,
                'backupDataProvider' => $backupDataProvider,
                'screenshotSearchModel' => $screenshotSearchModel,
                'screenshotDataProvider' => $screenshotDataProvider,
                'restoreSearchModel' => $restoreSearchModel,
                'restoreDataProvider' => $restoreDataProvider,
                'ItemsDataProvider' => $ItemsDataProvider,
                'VersionsDataProvider' => $VersionsDataProvider,
                'fs' => $fs,
                'date' => $date,
                'options' => $options,
            ]);
        } else if ($mode == 'probe') {
            //$model = $this->findModel($id);
            //$online = $model->runCommand('source /info; ping -nq -W 10 -c 1 "${gladosIp}"', 'C', 10)[1];
            $model->online = $model->runCommand('true', 'C', 10)[1] == 0 ? 1 : 0;
            $model->save(false);
            return $this->redirect(['ticket/view',
                'id' => $model->id,
                #'online' => $online,
            ]);
        } else if ($mode == 'report') {
            //$model = $this->findModel($id);

            $content = $this->renderPartial('report', [
                'model' => $model,
            ]);

            $title = 'Ticket für Prüfung "' . $model->exam->subject . ' - ' . $model->exam->name . '"';

            $pdf = new Pdf([
                'mode' => Pdf::MODE_UTF8, 
                'format' => Pdf::FORMAT_A4, 
                'orientation' => Pdf::ORIENT_PORTRAIT, 
                'filename' => 'Ticket-' . $model->token . '.pdf',
                'destination' => Pdf::DEST_BROWSER, 
                'content' => $content,  
                'options' => ['title' => $title],
                'methods' => [ 
                    'SetHeader' => [$title], 
                    'SetFooter' => ['{PAGENO}'],
                ]
            ]);

            // return the pdf output as per the destination setting
            return $pdf->render(); 
        }
    }

    /**
     * Creates a new Ticket model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @param string $mode
     * @param integer $exam_id
     * @param integer $count
     * @param string $type
     * @return mixed
     */
    public function actionCreate($mode = 'single', $exam_id = null, $count = 0, $type = 'anonymous')
    {

        if ($mode === 'single') {
            $model = new Ticket();
            $searchModel = new TicketSearch();

            if ($model->load(Yii::$app->request->post()) && $model->save()){
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('create', [
                    'model' => $model,
                    'searchModel' => $searchModel,
                ]);
            }
        }else if ($mode === 'many') {
            if ($type === 'anonymous') {
                $c = 0;
                for ($i = 1; $i <= $count; $i++) {
                    $model = new Ticket();
                    $model->exam_id = $exam_id;
                    $model->save() ? $c++ : null;
                }

                if ($count == $c) {
                    Yii::$app->session->addFlash('success', 'You have successfully created ' . $count . ' new Tickets.');
                    return $this->redirect(['exam/view', 'id' => $exam_id]);
                } else {
                    foreach ($model->getErrors() as $attribute => $value){
                        Yii::$app->session->addFlash('danger', $value);
                    }

                    return $this->redirect(['exam/view', 'id' => $exam_id]);
                }
            }else if($type == 'assigned'){

                $model = new \yii\base\DynamicModel(['names', 'class']);
                $model->addRule(['names'], 'required')
                      ->addRule('names', 'string')
                      ->addRule('class', 'string');

                
                $model->load(Yii::$app->request->post());
                $names = array_unique(array_filter(array_map('trim', preg_split('/\n|\r/', $model->names, -1, PREG_SPLIT_NO_EMPTY))));

                $c = 0;
                foreach($names as $key => $value){
                    $ticket = new Ticket();
                    $ticket->exam_id = $exam_id;
                    $ticket->test_taker = $value;
                    $ticket->save() ? $c++ : null;
                }

                if(count($names) != 0) {
                    if ($c == count($names)) {
                        Yii::$app->session->addFlash('success', 'You have successfully created ' . $c . ' new Tickets.');
                        return $this->redirect(['exam/view', 'id' => $exam_id]);
                    }else{
                        foreach ($ticket->getErrors() as $attribute => $value){
                            Yii::$app->session->addFlash('danger', $value);
                        }
                        return $this->redirect(['exam/view', 'id' => $exam_id]);
                    }
                }

                return $this->render('create-many', [
                    'model' => $model,
                    'names' => $names,
                ]);
            }
        }
    }


    /**
     * Updates an existing Ticket model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($mode = 'default', $id = null, $token = null)
    {
        if ($mode === 'default') {
            $model = $this->findModel($id);
            $searchModel = new TicketSearch();

            $model->scenario = YII_ENV_DEV ? Ticket::SCENARIO_DEV : Ticket::SCENARIO_DEFAULT;
            $model->download_lock = 0; #unlock the download
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                    'searchModel' => $searchModel,
                ]);
            }
        }else if ($mode === 'submit') {
            $params = Yii::$app->request->post('Ticket');
            $token = (isset($params['token']) && !empty($params['token'])) ? $params['token'] : null;
            $test_taker = (isset($params['test_taker']) && !empty($params['test_taker'])) ? $params['test_taker'] : null;

            if (($model = Ticket::findOne(['token' => $token])) === null) {
                $model = new Ticket(['scenario' => Ticket::SCENARIO_SUBMIT]);
                $model->token = $token;
                $model->token != null ? $model->addError('token', 'Ticket not found.') : null;

                return $this->render('submit', [
                    'model' => $model,
                ]);
            }else if ($test_taker === null) {
                $model->scenario = Ticket::SCENARIO_SUBMIT;
                $model->load(Yii::$app->request->post());
                $model->validate(['token'], true);
                $this->checkRbac($model);

                return $this->render('submit', [
                    'model' => $model,
                ]);
            }else{
                $model->scenario = Ticket::SCENARIO_SUBMIT;
                $this->checkRbac($model);
                if ($model->load(Yii::$app->request->post()) && $model->save()) {
                    return $this->redirect(['view', 'id' => $model->id]);
                }else{
                    return $this->render('submit', [
                        'model' => $model,
                    ]);
                }
            }
        }
    }

    /**
     * Deletes an existing Ticket model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id = null, $mode = 'single', $exam_id = null)
    {
        if ($mode == 'single') {
            $this->findModel($id)->delete();
            Yii::$app->session->addFlash('danger', 'The Ticket has been deleted successfully.');

            return $this->redirect(Yii::$app->session['ticketViewReturnURL']);
        }else if ($mode == 'many') {
            $query = Ticket::find()->where(['exam_id' => $exam_id]);
            Yii::$app->user->can('ticket/delete/all') ?: $query->own();
            $models = $query->all();

            $c = 0;
            foreach ($models as $key => $model){
                if($model->state == Ticket::STATE_OPEN){
                    $model->delete() ? $c++ : null;
                }
            }

            #TODO: errors?
            if($c == 0){
                Yii::$app->session->addFlash('danger', 'There are no Open Tickets to delete.');
                return $this->redirect(['exam/view', 'id' => $exam_id]);
            }

            Yii::$app->session->addFlash('danger', $c . ' Open Tickets have been deleted successfully.');
            return $this->redirect(['exam/view', 'id' => $exam_id]);
        }
    }

    /**
     * Echoes the MD5 sum of the exam file for the client to verify.
     *
     * @param string $token
     * @return The response object or an array with the error description
     */
    public function actionMd5($token)
    {

        $model = Ticket::findOne(['token' => $token]);
        if (!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        } else {
            echo $model->exam->md5;
            return;
        }
    }

    /**
     * Returns the exam config in JSON.
     *
     * @param string $token
     * @return The JSON response
     */
    public function actionConfig($token)
    {

        $model = Ticket::findOne(['token' => $token]);
        if (!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        } else {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'config' => [
                    'grp_netdev' => boolval($model->exam->{"grp_netdev"}),
                    'allow_sudo' => boolval($model->exam->{"allow_sudo"}),
                    'allow_mount' => boolval($model->exam->{"allow_mount"}),
                    'firewall_off' => boolval($model->exam->{"firewall_off"}),
                    'screenshots' => boolval($model->exam->{"screenshots"}),
                    'url_whitelist' => implode(PHP_EOL, preg_split("/\r\n|\n|\r/", $model->exam->{"url_whitelist"}, null, PREG_SPLIT_NO_EMPTY)),
                ]
            ];
        }
    }

    /**
     * Downloads an exam file after checking ticket validity.
     *
     * @param string $token
     * @return The response object or an array with the error description
     */
    public function actionDownload($token)
    {

        $model = Ticket::findOne(['token' => $token]);

        if (!$model || !$model->valid){
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [ 'code' => 403, 'msg' => 'The provided ticket is invalid.' ];
        }

        if (!Yii::$app->file->set($model->exam->file)->exists){
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [ 'code' => 404, 'msg' => 'The exam file cannot be found.' ];
        }

        if($model->download_lock != 0) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [ 'code' => 403, 'msg' => 'Another instance is already running; ' .
                                             'multiple downloads are not allowed.' ];
        }

        $query = Ticket::find()
            ->where(['not', ['start' => null]])
            ->andWhere(['end' => null])
            ->andWhere(['download_lock' => 1]);

        if(intval($query->count()) >= 10){
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'code' => 509,
                'msg' => 'The server is busy, please wait. Retry in {i} seconds.',
                'wait' => 20,
            ];
        }

        $model->scenario = Ticket::SCENARIO_DOWNLOAD;

        $model->bootup_lock = 1;
        $model->download_lock = 1;
        $model->start = $model->state == 0 ? new Expression('NOW()') : $model->start;
        $model->ip = Yii::$app->request->userIp;
        $model->save();

        ignore_user_abort(true);
        \Yii::$app->response->bandwidth = 20 * 1024 * 1024; //20MB per second

        # log activity before [[send()]] is called upon the request.
        \Yii::$app->response->on(\app\components\customResponse::EVENT_BEFORE_SEND, function($event) {
            $ticket = Ticket::findOne($event->data->id);
            $ticket->scenario = Ticket::SCENARIO_DOWNLOAD;
            $ticket->download_progress = 0;
            $ticket->client_state = "download in progress";
            $ticket->save();

            $act = new Activity([
                'ticket_id' => $event->data->id,
                'description' => 'Exam download successfully requested by ' . 
                $event->data->ip . ' from ' . ( $event->data->test_taker ? $event->data->test_taker :
                'Ticket with token ' . $event->data->token ) . '.'
            ]);
            $act->save();
        }, $model);
       
        # calculate the percentage, write it to the database and look if the client side aborted the download 
        \Yii::$app->response->on(\app\components\customResponse::EVENT_WHILE_SEND, function($event) {
            $ticket = Ticket::findOne($event->data->id);
            $ticket->scenario = Ticket::SCENARIO_DOWNLOAD;

            if(connection_aborted()){
                $ticket->download_lock = 0;
                $ticket->client_state = "aborted, waiting for download";
                $ticket->save();

                $act = new Activity([
                    'ticket_id' => $event->data->id,
                    'description' => 'Exam download aborted by ' . $event->data->ip . 
                    ' from ' . ( $event->data->test_taker ? $event->data->test_taker :
                    'Ticket with token ' . $event->data->token ) . ' (client side).'
                ]);
                $act->save();
                die();
            }

            $ticket->download_progress = $event->sender->progress/filesize($event->data->exam->file);
            $ticket->download_lock = 1;
            $ticket->client_state = "download in progress";
            $ticket->save();

        }, $model);

        # log that the [[send()]] process ended. TODO: success or not?
        \Yii::$app->response->on(\app\components\customResponse::EVENT_AFTER_SEND, function($event) {
            $ticket = Ticket::findOne($event->data->id);
            $ticket->scenario = Ticket::SCENARIO_DOWNLOAD;
            $ticket->download_progress = 1;
            $ticket->download_lock = 0;
            $ticket->client_state = "download finished";
            $ticket->save();

            $act = new Activity([
                'ticket_id' => $event->data->id,
                'description' => 'Exam download finished by ' . $event->data->ip .
                ' from ' . ( $event->data->test_taker ? $event->data->test_taker :
                'Ticket with token ' . $event->data->token ) . '.'
            ]);
            $act->save();

            /* if there is a backup available restore the latest */
            $backupSearchModel = new BackupSearch();
            $backupDataProvider = $backupSearchModel->search($ticket->token);
            if ($backupDataProvider->totalCount > 0) {
                $restoreDaemon = new Daemon();
                /* run the restore daemon in the foreground */
                $pid = $restoreDaemon->startRestore($ticket->id, '/', 'now', false, '/run/initramfs/backup/' . $ticket->exam->backup_path);
            }
            //$ticket->continueBootup();
            $ticket->runCommand('echo 0 > /run/initramfs/restore');
            //$ticket->bootup_lock = 0;
            $ticket->save();

        }, $model);

        /* Start a new backup Daemon on the background */
        $searchModel = new DaemonSearch();
        if($searchModel->search([])->totalCount < 3){
            $backupDaemon = new Daemon();
            $backupDaemon->startBackup();
        }
        if($searchModel->search([])->totalCount < 3){
            $backupDaemon = new Daemon();
            $backupDaemon->startBackup();
        }

        return \Yii::$app->response->sendFile($model->exam->file);
    }

    /**
     * Changes the state of a client.
     *
     * @param string $token
     * @param string $state
     */
    public function actionNotify($token, $state)
    {
        $model = Ticket::findOne(['token' => $token]);
        $model->scenario = Ticket::SCENARIO_NOTIFY;

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if($model !== null) {
            $model->client_state = $state;
            if ($state == "bootup complete.") {
                $model->bootup_lock = 0;
            }
            if ($model->save()) {
                return [ 'code' => 200, 'msg' => 'Client state changed.' ];
            }
        }

        return [ 'code' => 418, 'msg' => 'Client state not changed.' ]; // I'm a teapot

    }

    /**
     * Finishes an exam.
     *
     * @param string $token
     * @return The response object or an array with the error description
     */
    public function actionFinish($token)
    {
        $model = Ticket::findOne(['token' => $token]);
        $model->scenario = Ticket::SCENARIO_FINISH;

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if ($model === null){
            return [ 'code' => 403, 'msg' => 'The provided ticket is invalid.' ];
        }
        if ($model->state != Ticket::STATE_RUNNING){
            return [ 'code' => 403, 'msg' => 'The provided ticket is not in running state.' ];
        }

        $model->end = new Expression('NOW()');
        $model->save();

        $act = new Activity([
            'ticket_id' => $model->id,
            'description' => 'Exam finished by ' . ( $model->test_taker ?
            $model->test_taker : 'Ticket with token ' . $token ) . '.',
        ]);
        $act->save();

        /* Start a new backup Daemon on the background */
        $searchModel = new DaemonSearch();
        if($searchModel->search([])->totalCount < 3){
            $backupDaemon = new Daemon();
            $backupDaemon->startBackup();
        }

        return [ 'code' => 200, 'msg' => 'Exam finished successfully' ];

    }

    /**
     * Starts a background daemon to backup the ticket.
     *
     * @param integer $id
     * @return The response object
     */
    public function actionBackup($id)
    {
        $model = $this->findModel($id);

        $daemon = new Daemon();
        $pid = $daemon->startBackup($id);

        if(Yii::$app->request->isAjax){
            return $this->runAction('view', ['id' => $id]);
        }else{
            return $this->redirect(['view', 'id' => $id, '#' => 'backups']);
        }

    }

    /**
     * Starts a background daemon to restore a specific file by date.
     *
     * @param integer $id id of the Ticket model.
     * @param string $date date string of the Backup model.
     * @param string $file the file to restore
     * @return The response object
     */
    public function actionRestore($id, $file, $date = 'now')
    {
        $model = $this->findModel($id);
        $date = $date == 'all' ? 'now' : $date;

        $daemon = new Daemon();
        $pid = $daemon->startRestore($id, $file, $date);

        Yii::$app->session->addFlash('info', 'Restore started.');

        if(Yii::$app->request->isAjax){
            return $this->runAction('view', ['id' => $id]);
        }else{
            return $this->redirect(['view', 'id' => $id]);
        }

    }


    /**
     * Generates the needed ssh keys and populates the public key.
     * If the key could not be generated, a 500 HTTP exception will be thrown.
     *
     * @return string the public key
     * @throws ServerErrorHttpException if the key cannot be generated
     */
    public function actionSshKey()
    {

        \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;

        $dotSSH = \Yii::$app->basePath . '/' . '.ssh';
        $pubKeyFile = $dotSSH . '/' . 'rsa.pub';
        $privKeyFile = $dotSSH . '/' . 'rsa';
        if (!file_exists($dotSSH)) {
            mkdir($dotSSH, 0700);
        }

        if (!file_exists($pubKeyFile) || !file_exists($privKeyFile)) {
            exec("ssh-keygen -t rsa -f " . $privKeyFile . " -N ''", $output, $retval);
        }

        if (file_exists($pubKeyFile)) {
            return file_get_contents($pubKeyFile);
        } else {
            throw new ServerErrorHttpException('The public key could not be generated.');
        }
    }

    /**
     * Finds the Ticket model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     * @return Ticket the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Ticket::findOne($id)) !== null) {
            if ($this->checkRbac($model)) {
                return $model;
            }
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Finds the Ticket model based on its primary key value.
     *
     * @param Ticket $model the Ticket model
     * @return boolean whether acces is allowed or not
     * @throws ForbiddenHttpException if the access control failed.
     */
    protected function checkRbac($model)
    {
        $r = \Yii::$app->controller->id . '/' . \Yii::$app->controller->action->id;
        if(Yii::$app->user->can($r . '/all') || $model->exam->user_id == Yii::$app->user->id){
            return true;
        }else{
            throw new ForbiddenHttpException('You are not allowed to ' . \Yii::$app->controller->action->id . 
                    ' this ' . \Yii::$app->controller->id . '.');
            return false;
        }
    }

}
