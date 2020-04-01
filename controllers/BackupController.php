<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Ticket;
use app\models\Backup;
use app\models\RdiffFileSystem;
use app\models\Daemon;
use app\models\DaemonSearch;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\data\ArrayDataProvider;

class BackupController extends Controller
{

    /**
     * Lists all Backup models.
     * @return mixed
     */
    public function actionIndex($ticket_id)
    {
        return $this->redirect(['ticket/view', 'id' => $ticket_id, '#' => 'tab_backups']);
    }

    /**
     * Displays the backup log by Backup model.
     * @param integer $ticket_id id of the Ticket model.
     * @param string $date date string of the Backup model.
     * @return The response object
     */
    public function actionLog($ticket_id, $date)
    {
        $ticket = Ticket::findOne($ticket_id);
        $backup = Backup::findOne($ticket->token, $date);

        return $this->renderAjax('/backup/log', [
            'log' => $backup->backupLog,
        ]);
    }

    public function actionFile ($ticket_id, $path, $date = 'now')
    {
        $ticket = Ticket::findOne($ticket_id);

        $fs = new RdiffFileSystem([
            'root' => '/home/user',
            'location' => \Yii::$app->params['backupPath'] . '/' . $ticket->token,
            'restoreUser' => 'root',
            'restoreHost' => $ticket->ip,
        ]);

        $file = $fs->slash($path);

        if ($file !== null) {
            $contents = $file->versionAt($date)->restore(true);
        } else {
            throw new NotFoundHttpException($path . ': No such file or directory.');
        }
        
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $arr = [
            'm3u8' => 'application/x-mpegURL',
            'ts' => 'video/MP2T',
        ];
        $mimeType = array_key_exists($ext, $arr) ? $arr[$ext] : 'application/octet-stream';
        if ($ext == 'm3u8' && ($ticket->state == Ticket::STATE_CLOSED || $ticket->state == Ticket::STATE_SUBMITTED)) {
            $contents = str_replace("#EXT-X-PLAYLIST-TYPE:EVENT", "#EXT-X-PLAYLIST-TYPE:VOD", $contents) . "#EXT-X-ENDLIST" . PHP_EOL;
        }

        return Yii::$app->response->sendContentAsFile($contents, $fs->slash($path)->basename, [
            'mimeType' => $mimeType,
            'inline' => false,
        ]);
    }

    public function actionBrowse($ticket_id, $path = '/', $date = 'all')
    {

		$ticket = Ticket::findOne($ticket_id);

        if (!file_exists(\Yii::$app->params['backupPath'] . '/' . $ticket->token)) {
            return '<span>' . \Yii::t('backups', 'No Backup yet.') . '</span>';
        }

		$fs = new RdiffFileSystem([
            'root' => '/home/user',
            'location' => \Yii::$app->params['backupPath'] . '/' . $ticket->token,
            'restoreUser' => 'root',
            'restoreHost' => $ticket->ip,
        ]);

        $models = $fs->slash($path)->versionAt($date)->contents;
        $versions = $fs->slash($path)->versions;
        array_unshift($versions , 'now');
        array_unshift($versions , 'all');

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

        if (($ticket = Ticket::findOne($ticket_id)) !== null){
	        if (Yii::$app->request->isAjax) {
                return $this->renderAjax('/backup/browse', [
                    'ItemsDataProvider' => $ItemsDataProvider,
                    'VersionsDataProvider' => $VersionsDataProvider,
                    'ticket' => $ticket,
                    'fs' => $fs,
                    'date' => $date,
                ]);
	        }else{
                /*return $this->render('/backup/browse', [
                    'ItemsDataProvider' => $ItemsDataProvider,
                    'VersionsDataProvider' => $VersionsDataProvider,
                    'ticket' => $ticket,
                    'fs' => $fs,
                    'date' => $date,
                ]);*/
	        	return $this->redirect(['ticket/view', 'id' => $ticket_id, '#' => 'tab_browse']);
	        }
        }else{
            throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
        }


    }

}