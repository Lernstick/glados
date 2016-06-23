<?php

namespace app\controllers;

use Yii;
use app\models\Restore;
use app\models\RestoreSearch;
use app\models\Ticket;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RestoreController implements the CRUD actions for Restore model.
 */
class RestoreController extends Controller
{


    /**
     * Lists all Restore models.
     * @return mixed
     */
    public function actionIndex($ticket_id)
    {
        return $this->redirect(['ticket/view', 'id' => $ticket_id, '#' => 'tab_restores']);
    }


}
