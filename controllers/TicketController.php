<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
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
use app\models\History;
use app\models\HistorySearch;
use app\models\Exam;
use app\models\EventItem;
use app\models\Stats;
use app\models\Daemon;
use app\models\DaemonSearch;
use app\models\RdiffFileSystem;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use yii\filters\VerbFilter;
use yii\db\Expression;
use kartik\mpdf\Pdf;
use \mPDF;
use app\components\customResponse;
use app\components\AccessRule;
use yii\data\ArrayDataProvider;
use yii\widgets\ActiveForm;

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
                    'live' => ['get', 'post'],
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
                            'download',  // request download of exam
                            'md5',       // verify exam file (old)
                            'config',    // retrieve exam config
                            'ssh-key',   // get public server ssh key
                            'notify',    // notify a new client status
                            'finish',    // finish exam
                            'status',    // show the status of the exam
                            'live',      // post the live stream
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
     *
     * @param string $mode mode
     * @param string $attr attribute to make a list of
     * @param string $q query
     * @return mixed
     */
    public function actionIndex($mode = null, $attr = null, $q = null, $page = 1, $per_page = 10)
    {

        if ($mode === null) {
            Yii::$app->session['ticketViewReturnURL'] = Yii::$app->request->Url;

            $searchModel = new TicketSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        } else if ($mode == 'list') {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $out = [];
            if (!is_null($attr)) {
                $searchModel = new TicketSearch();
                if ($attr == 'testTaker') {
                    $out = $searchModel->selectList('test_taker', $q, $page, $per_page);
                } else if ($attr == 'token') {
                    $out = $searchModel->selectList('token', $q, $page, $per_page);
                } else if ($attr == 'client_state') {
                    $out = $searchModel->selectList('client_state', $q, $page, $per_page);
                } else if ($attr == 'backup_state') {
                    $out = $searchModel->selectList('backup_state', $q, $page, $per_page);
                } else if ($attr == 'restore_state') {
                    $out = $searchModel->selectList('restore_state', $q, $page, $per_page);
                }
            }
            return $out;
        }


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

            $lastState = Yii::$app->session['ticketLastState' . $model->id];
            if(isset($lastState) && $lastState != $model->state){
                Yii::$app->session->addFlash('info', Yii::t('ticket', 'The Ticket state has changed from {from} to {to}', [
                    'from' => Yii::$app->formatter->format($lastState, 'state'),
                    'to' => Yii::$app->formatter->format($model->state, 'state')
                ]));
            }
            Yii::$app->session['ticketLastState' . $model->id] = $model->state;

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

            $historySearchModel = new HistorySearch();
            $historyQueryParams = array_key_exists('HistorySearch', Yii::$app->request->queryParams)
                ? Yii::$app->request->queryParams['HistorySearch']
                : [];
            $historyParams = array_merge($historyQueryParams, [
                'table' => 'ticket',
                'row' => $id,
            ]);
            $historyParams = ['HistorySearch' => $historyParams];
            $historyDataProvider = $historySearchModel->search($historyParams);
            $historyDataProvider->pagination->pageParam = 'hist-page';
            $historyDataProvider->pagination->pageSize = 10;

            $options = [
                'showDotFiles' => boolval($showDotFiles),
            ];

            $fs = new RdiffFileSystem([
                'root' => $model->exam->backup_path,
                'location' => realpath(\Yii::$app->params['backupPath'] . '/' . $model->token),
                'restoreUser' => 'root',
                'restoreHost' => $model->ip,
                'options' => $options,
            ]);

            if ($date == null) {
                $date = ($model->state == Ticket::STATE_CLOSED || $model->state == Ticket::STATE_SUBMITTED) ? $fs->newestBackupVersion : 'all';
            }

            if (file_exists(\Yii::$app->params['backupPath'] . '/' . $model->token)) {
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
                'activitySearchModel' => $activitySearchModel,
                'activityDataProvider' => $activityDataProvider,
                'backupSearchModel' => $backupSearchModel,
                'backupDataProvider' => $backupDataProvider,
                'screenshotSearchModel' => $screenshotSearchModel,
                'screenshotDataProvider' => $screenshotDataProvider,
                'restoreSearchModel' => $restoreSearchModel,
                'restoreDataProvider' => $restoreDataProvider,
                'historySearchModel' => $historySearchModel,
                'historyDataProvider' => $historyDataProvider,
                'ItemsDataProvider' => $ItemsDataProvider,
                'VersionsDataProvider' => $VersionsDataProvider,
                'fs' => $fs,
                'date' => $date,
                'options' => $options,
            ]);
        } else if ($mode == 'probe') {
            //$online = $model->runCommand('source /info; ping -nq -W 10 -c 1 "${gladosIp}"', 'C', 10)[1];
            $model->online = $model->runCommand('true', 'C', 10)[1] == 0 ? true : false;
            $model->save(false);
            return $this->redirect(['ticket/view',
                'id' => $model->id,
                #'online' => $online,
            ]);
        } else if ($mode == 'report') {
            $content = $this->renderPartial('report', [
                'model' => $model,
            ]);

            $title = \Yii::t('exams', 'Ticket for "{exam} - {subject}"', [
                'exam' => $model->exam->subject,
                'subject' => $model->exam->name
            ]);

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
                    'attr' => null,
                ]);
            }
        } else if ($mode === 'many') {
            if ($type === 'anonymous') {
                $c = 0;
                for ($i = 1; $i <= $count; $i++) {
                    $model = new Ticket();
                    $model->exam_id = $exam_id;
                    $model->save() ? $c++ : null;
                }

                if ($count == $c) {
                    Yii::$app->session->addFlash('success', Yii::t('ticket', 'You have successfully created {n} new Tickets.', ['n' => $count]));
                    return $this->redirect(['exam/view', 'id' => $exam_id]);
                } else {
                    foreach ($model->getErrors() as $attribute => $value){
                        Yii::$app->session->addFlash('danger', $value);
                    }

                    return $this->redirect(['exam/view', 'id' => $exam_id]);
                }
            } else if ($type == 'assigned') {

                $model = new \yii\base\DynamicModel(['names', 'exam_id', 'class']);
                $model->addRule(['names', 'exam_id'], 'required')
                      ->addRule('names', 'string')
                      ->addRule('exam_id', 'integer')
                      ->addRule('class', 'string');
                $model->exam_id = $exam_id;

                if ($model->load(Yii::$app->request->post()) && $model->validate()){
                
                    $names = array_unique(array_filter(array_map('trim', preg_split('/\n|\r/', $model->names, -1, PREG_SPLIT_NO_EMPTY))));

                    $c = 0;
                    foreach ($names as $key => $value) {
                        $ticket = new Ticket();
                        $ticket->exam_id = $model->exam_id;
                        $ticket->test_taker = $value;
                        $ticket->save() ? $c++ : null;
                    }

                    if (count($names) != 0) {
                        if ($c == count($names)) {
                            Yii::$app->session->addFlash('success', Yii::t('ticket', 'You have successfully created {n} new Tickets.', ['n' => $c]));
                            return $this->redirect(['exam/view', 'id' => $model->exam_id]);
                        } else {
                            foreach ($ticket->getErrors() as $attribute => $value){
                                Yii::$app->session->addFlash('danger', $value);
                            }
                            return $this->redirect(['exam/view', 'id' => $model->exam_id]);
                        }
                    }
                }

                $searchModel = new TicketSearch();
                $examModel = Exam::findOne($exam_id);

                return $this->render('create-many', [
                    'model' => $model,
                    'examModel' => $examModel,
                    'searchModel' => $searchModel,
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
    public function actionUpdate($mode = 'default', $id = null, $token = null, $attr = null, $validate = false)
    {
        if ($mode === 'default') {
            $model = $this->findModel($id);
            $searchModel = new TicketSearch();

            $model->scenario = YII_ENV_DEV ? Ticket::SCENARIO_DEV : Ticket::SCENARIO_DEFAULT;
            #$model->download_lock = 0; #unlock the download
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                    'searchModel' => $searchModel,
                    'attr' => $attr
                ]);
            }
        } else if ($mode === 'editable') {
            $model = $this->findModel($id);
            $searchModel = new TicketSearch();

            $model->scenario = YII_ENV_DEV ? Ticket::SCENARIO_DEV : Ticket::SCENARIO_DEFAULT;
            if ($model->load(Yii::$app->request->post())) {
                \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return $validate ? ActiveForm::validate($model) : $model->save();
            } else {
                return $this->renderAjax('_form', [
                    'model' => $model,
                    'searchModel' => $searchModel,
                    'attr' => $attr
                ]);
            }
        } else if ($mode === 'submit') {
            $params = Yii::$app->request->post('Ticket');
            $token = (isset($params['token']) && !empty($params['token'])) ? $params['token'] : null;
            $test_taker = (isset($params['test_taker']) && !empty($params['test_taker'])) ? $params['test_taker'] : null;

            if (($model = Ticket::findOne(['token' => $token])) === null) {
                $model = new Ticket(['scenario' => Ticket::SCENARIO_SUBMIT]);
                $model->token = $token;
                $model->token != null ? $model->addError('token', Yii::t('ticket', 'Ticket not found.')) : null;

                return $this->render('submit', [
                    'model' => $model,
                ]);
            } else if ($test_taker === null) {
                $model->scenario = Ticket::SCENARIO_SUBMIT;
                $model->load(Yii::$app->request->post());
                $model->validate(['token'], true);
                $this->checkRbac($model);

                return $this->render('submit', [
                    'model' => $model,
                ]);
            } else {
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
            $model = $this->findModel($id);

            if ($model->backup_lock == 0 && $model->restore_lock == 0 &&  $model->download_lock == 0) {
                $model->delete();
                Yii::$app->session->addFlash('danger', Yii::t('ticket', 'The Ticket has been deleted successfully.'));
                return $this->redirect(Yii::$app->session['ticketViewReturnURL']);
            } else {
                Yii::$app->session->addFlash('danger', \Yii::t('ticket', 'The ticket is still locked by a daemon and can therefore not be deleted.'));
                return $this->redirect(['ticket/view', 'id' => $id]);
            }
        } else if ($mode == 'manyOpen' || $mode == 'many') {
            $query = Ticket::find()->where(['exam_id' => $exam_id]);
            Yii::$app->user->can('ticket/delete/all') ?: $query->own();
            $models = $query->all();

            $c = 0;
            if ($mode == 'manyOpen'){
                foreach ($models as $key => $model){
                    if ($model->state == Ticket::STATE_OPEN){
                        $model->delete() ? $c++ : null;
                    }
                }
            } else if ($mode == 'many'){
                foreach ($models as $key => $model){
                    $model->delete() ? $c++ : null;
                }
            }


            #TODO: errors?
            if($c == 0){
                if ($mode == 'manyOpen') {
                    Yii::$app->session->addFlash('danger', Yii::t('ticket', 'There are no Open Tickets to delete.'));
                } else if ($mode == 'many'){
                    Yii::$app->session->addFlash('danger', Yii::t('ticket', 'There are no Tickets to delete.'));
                }
                return $this->redirect(['exam/view', 'id' => $exam_id]);
            }

            if ($mode == 'manyOpen') {
                Yii::$app->session->addFlash('danger', Yii::t('ticket', '{n} Open Tickets have been deleted successfully.', ['n' => $c]));
            } else if ($mode == 'many'){
                Yii::$app->session->addFlash('danger', Yii::t('ticket', '{n} Tickets have been deleted successfully.', ['n' => $c]));
            }
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
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
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
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        } else {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'config' => array_merge([
                    'grp_netdev' => boolval($model->exam->{"grp_netdev"}),
                    'allow_sudo' => boolval($model->exam->{"allow_sudo"}),
                    'allow_mount' => boolval($model->exam->{"allow_mount"}),
                    'firewall_off' => boolval($model->exam->{"firewall_off"}),
                ], $model->exam->settings)
            ];
        }
    }

    /**
     * Renders the exam download prompt.
     *
     * @param string $token
     * @return mixed
     */
    public function actionDownload($token = null, $step = 1)
    {
        $this->layout = 'client';
        $model = Ticket::findOne(['token' => $token]);

        if ($step == 1) {
            if ($model === null) {
                $model = new Ticket(['scenario' => Ticket::SCENARIO_DEFAULT]);
                !empty($token) ? $model->addError('token', \Yii::t('ticket', 'Ticket not found.')) : null;
                $model->token = $token;
            }
            return $this->render('token-request', [
                'model' => $model,
            ]);            
        } else if ($step == 2) {
            if ($model === null) {
                $model = new Ticket(['scenario' => Ticket::SCENARIO_DEFAULT]);
                $token !== null ? $model->addError('token', \Yii::t('ticket', 'Ticket not found.')) : null;
                $model->token = $token;
                return $this->render('token-request', [
                    'model' => $model,
                ]);                
            } else if (!$model->valid) {
                $model->addError('token', \Yii::t('ticket', 'The ticket has expired.'));
                return $this->render('token-request', [
                    'model' => $model,
                ]);                
            } else if (!$model->exam->fileConsistency) {
                $model->addError('token', \Yii::t('ticket', 'The exam file is not valid.'));
                return $this->render('token-request', [
                    'model' => $model,
                ]);                
            } else if ($model->download_lock != 0) {
                $model->addError('token', \Yii::t('ticket', 'Another instance is already running, '
                                        . 'multiple downloads are not allowed.'));
                return $this->render('token-request', [
                    'model' => $model,
                ]);                
            } else if ($model->backup_lock != 0) {
                $model->addError('token', \Yii::t('ticket', 'The ticket is currently in processing for backup. '
                                        . 'Please try again in a minute.'));
                $this->startDaemon();
                return $this->render('token-request', [
                    'model' => $model,
                ]);
            } else if ($model->restore_lock != 0) {
                $model->addError('token', \Yii::t('ticket', 'The ticket is currently in processing for restore. '
                                        . 'Please try again in a minute.'));
                $this->startDaemon();
                return $this->render('token-request', [
                    'model' => $model,
                ]);
            } else {

                $model->scenario = Ticket::SCENARIO_DOWNLOAD;
                $model->bootup_lock = 1;
                $model->download_request = new Expression('NOW()');
                $model->start = $model->state == 0 ? new Expression('NOW()') : $model->start;
                $model->ip = Yii::$app->request->userIp;
                $model->client_state = yiit('ticket', 'exam requested sccessfully');
                $model->download_progress = 0;
                $model->save();

                if ($model->test_taker) {
                    $act = new Activity([
                        'ticket_id' => $model->id,
                        'description' => yiit('activity', 'Exam download successfully requested by {ip} from {test_taker}.'),
                        'description_params' => [
                            'ip' => $model->ip,
                            'test_taker' => $model->test_taker,
                        ],
                        'severity' => Activity::SEVERITY_SUCCESS,
                    ]);
                } else {
                    $act = new Activity([
                        'ticket_id' => $model->id,
                        'description' => yiit('activity', 'Exam download successfully requested by {ip} from Ticket with token {token}.'),
                        'description_params' => [
                            'ip' => $model->ip,
                            'token' => $model->token,
                        ],
                        'severity' => Activity::SEVERITY_SUCCESS,
                    ]);
                }
                $act->save();

                $this->startDaemon();

                return $this->redirect(['download', 'token' => $model->token, 'step' => 3]);
            }
        } else {
            return $this->render('download', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Returns the exam status view for the student.
     *
     * @param string $token
     * @return mixed 
     */
    public function actionStatus($token)
    {
        $this->layout = 'client';
        $model = Ticket::findOne(['token' => $token]);

        return $this->render('/result/_view', [
            'title' => 'Exam Status',
            'model' => $model,
        ]);
    }

    /**
     * Changes the state of a client.
     *
     * @param string $token
     * @param string $state
     * @return array JSON response
     */
    public function actionNotify($token, $state)
    {
        $model = Ticket::findOne(['token' => $token]);
        $model->scenario = Ticket::SCENARIO_NOTIFY;

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if($model !== null) {
            if ($state == yiit('ticket', 'bootup complete.')) {
                $model->bootup_lock = 0;
            }

            // split the state in params and string if it matches the below regex
            if (preg_match('/^(.*?) reconnected \((.*?)\)\.$/', $state, $matches) == 1) {
                $model->client_state = yiit('ticket', '{interface} reconnected ({count}).');
                $model->client_state_params = [
                    'interface' => $matches[1],
                    'count' => $matches[2]
                ];
            } else {
                $model->client_state = $state;
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
        $model->last_backup = 0;
        $model->save();

        // increment the stats for total duration and total completed exams
        // but count only exams whose duration is less or equal than 8 hours and more
        // or equal than 15 minutes.
        $model->refresh();
        if ($model->durationInSecs <= 28800 && $model->durationInSecs >= 900) {
            Stats::increment('total_duration', $model->durationInSecs);
            Stats::increment('completed_exams'); // +1
        }
  
        if ($model->test_taker) {
            $act = new Activity([
                'ticket_id' => $model->id,
                'description' => yiit('activity', 'Exam finished by {test_taker}.'),
                'description_params' => [ 'test_taker' => $model->test_taker ],
                'severity' => Activity::SEVERITY_INFORMATIONAL,
            ]);
        } else {
            $act = new Activity([
                'ticket_id' => $model->id,
                'description' => yiit('activity', 'Exam finished by Ticket with token {token}.'),
                'description_params' => [ 'token' => $token ],
                'severity' => Activity::SEVERITY_INFORMATIONAL,
            ]);
        }
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

        Yii::$app->session->addFlash('info', \Yii::t('ticket', 'Restore started.'));

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

        $dotSSH = \Yii::$app->params['dotSSH'];
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
            throw new ServerErrorHttpException(\Yii::t('ticket', 'The public key could not be generated.'));
        }
    }


    /**
     * Receive the uploaded POST raw data from ffmpeg and save it to
     * [[uploadPath]]/live/$token.jpg or send the stored image in case
     * of a GET request. If the stored file exceeds the age of 10 seconds
     * the command "service live_overview start" is requested to be
     * executed on the client.
     *
     * @param string $token
     * @return The response object or an array with the error description
     */
    public function actionLive($token)
    {

        $model = Ticket::findOne(['token' => $token]);
        $request = Yii::$app->request;
        $path = \Yii::$app->params['uploadPath'] . '/live';
        $file = $path . '/' . $token . '.jpg';

        if (!$model) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        } else if ($request->isGet) {

                /* @TODO put into if clause below */
                $eventItem = new EventItem([
                    'event' => 'agent/' . $model->token,
                    'priority' => 0,
                    'data' => 'startLive'
                ]);
                $eventItem->generate();

            if ($model->bootup_lock == 0 && $model->state == Ticket::STATE_RUNNING
                && (file_exists($file) && time() - filemtime($file) > 10) || !file_exists($file)
            ){
                $model->runCommandAsync('service live_overview start');
                /*$eventItem = new EventItem([
                    'event' => 'startLiveStream/' . $model->token,
                    'priority' => 0,
                ]);
                $eventItem->generate();*/
            }
            if (file_exists($file)) {
                return \Yii::$app->response->sendFile($file, 'live.jpg', [
                    'mimeType' => 'image/jpeg',
                    'inline' => true
                ]);
            } else {
                throw new NotFoundHttpException(Yii::t('app', 'File not found.'));
            }
        } else if ($request->isPost) {
            if ($model->state == Ticket::STATE_RUNNING){
                if (!is_dir($path)) {
                    mkdir($path);
                }
                file_put_contents($file, $request->getRawBody());
                $eventItem = new EventItem([
                    'event' => 'ticket/' . $model->id,
                    'priority' => 0,
                    'data' => [
                        'live' => [
                            'base64' => base64_encode($request->getRawBody()),
                        ],
                    ],
                ]);
                $eventItem->generate();
            } else {
                throw new BadRequestHttpException(Yii::t('app', 'Ticket is not in running state.'));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action) 
    { 
        // Remove CSRF validation on actionLive calls
        if ($action->id == 'live') {
            $this->enableCsrfValidation = false; 
        }
        return parent::beforeAction($action); 
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
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * Checks RBAC permission on a Ticket
     *
     * @param Ticket $model the Ticket model
     * @return boolean whether access is allowed or not
     * @throws ForbiddenHttpException if the access control failed.
     */
    protected function checkRbac($model)
    {
        $r = \Yii::$app->controller->id . '/' . \Yii::$app->controller->action->id;
        if(Yii::$app->user->can($r . '/all') || $model->exam->user_id == Yii::$app->user->id){
            return true;
        }else{
            throw new ForbiddenHttpException(\Yii::t('app', 'You are not allowed to {action} this {item}.', [
                'action' => \Yii::$app->controller->action->id,
                'item' => \Yii::$app->controller->id
            ]));
            return false;
        }
    }

    /**
     * Starts a daemon if none is running already
     *
     * @return void
     */
    public function startDaemon()
    {
        # search for running daemons
        $daemonSearchModel = new DaemonSearch();
        $daemonDataProvider = $daemonSearchModel->search(['DaemonSearch' => ['description' => 'Daemon base controller']]);
        $count = $daemonDataProvider->getTotalCount();

        # if no daemon is running already, start one
        if($count == 0){
            $daemon = new Daemon();
            $daemon->startDaemon();
        }
    }

}
