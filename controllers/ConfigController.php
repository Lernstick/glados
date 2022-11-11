<?php

namespace app\controllers;

use Yii;
use yii\web\NotFoundHttpException;

/**
 * ConfigController implements the CRUD actions for Config model.
 */
class ConfigController extends BaseController
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \app\components\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'info',  // public accessible information
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
     * Displays public accessible information about the server, such as its version.
     *
     * @return mixed
     */
    public function actionInfo()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $str = strval(shell_exec("rdiff-backup --version"));
        if (preg_match('/(\d+\.?)+$/', $str, $matches) !== 0) {
            $v = $matches[0];
            if (version_compare($v, '2.0', '<')) { // if $v < 2.0
                $wants_rdiff_backup_version = '<2.0';
            } else {
                $wants_rdiff_backup_version = '>=2.0';
            }
        } else {
            $v = 'no rdiff-backup found';
            $wants_rdiff_backup_version = '==no rdiff-backup found on server';
        }
        return [
            "server_version" => \Yii::$app->version,
            "rdiff_backup_version" => $v,
            "wants_client_version" => ">=1.0.19", /* version of lernstick-exam-client debian package */
            "wants_lernstick_version" => ">=20221008", // 2022-10-08, notice without the dashes (from /usr/local/lernstick.html)
            "wants_lernstick_flavor" => "exam", // "exam" or "standard"
            "wants_rdiff_backup_version" => $wants_rdiff_backup_version,
        ];
    }
}
