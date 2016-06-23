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
     * Displays a backup Errors by Backup model.
     * @param integer $ticket_id id of the Ticket model.
     * @param string $date date string of the Backup model.
     * @return The response object
     */
    public function actionViewErrors($ticket_id, $date)
    {
        $ticket = Ticket::findOne($ticket_id);
        $backup = Backup::findOne($ticket->token, $date);

        return $this->renderAjax('/backup/errors', [
            'errors' => $backup->errorLog,
        ]);
    }

    public function actionBrowse($ticket_id, $path = '/')
    {

		$ticket = Ticket::findOne($ticket_id);

		$rootDir = \Yii::getAlias('@app/backups/' . $ticket->token);

		$fs = new RdiffFileSystem([
            'root' => '/home/user',
            'location' => $rootDir,
            'restoreUser' => 'root@',
            'restoreHost' => $ticket->ip,
            'ticket' => $ticket,
        ]);
        //$x = 2;

		//return \Yii::$app->response->sendContentAsFile($fs->slash('Schreibtisch/file.txt')->versionAt('2016-06-01T12:44:33+02:00')->contents, basename('Schreibtisch/file.txt'));		

        $x = $fs->slash('Schreibtisch/file.txt')->versionAt('2016-06-01T12:44:33+02:00')->restore(true);

        //$model = new Daemon();
        //$model->start('restore/run', $ticket->id, $fs->slash('/Schreibtisch/file.txt')->path);

//    	$x = $fs->slash($path)->slash('Schreibtisch/file.txt')->versionAt('2016-06-01T12:44:33+02:00')->restore();

    	//return \Yii::$app->response->sendContentAsFile("asdasd" . $x, basename($fs->slash($path)->slash('Schreibtisch/file.txt')->path));
    	//echo '<br>';
    	//$x = $fs->slash($path)->slash('Schreibtisch/file.txt.notexist')->path;
    	//$x = $fs->slash($path)->slash('Schreibtisch/file.txt.notexist')->propertiesPopulated;
    	//var_dump($fs->slash($path)->slash('Schreibtisch/file.txt.notexist')->versions);
    	//echo '<br>';

        if (($ticket = Ticket::findOne($ticket_id)) !== null){
	        if (Yii::$app->request->isAjax) {
		        return $this->renderAjax('/ticket/_backup-errors', [
	            	'errors' => [$x],
	        	]);
	        }else{
	        	return $this->redirect(['ticket/view', 'id' => $ticket_id, '#' => 'tab_browse']);
	        }
        }else{
            throw new NotFoundHttpException('The requested page does not exist.');
        }


    }

}