<?php

namespace app\models;

use Yii;
use app\models\RdiffFileSystem;
use yii\data\ArrayDataProvider;

/**
 * RdiffFileSystemSearch represents the model behind the search form about `app\models\RdiffFileSystem`.
 */
class RdiffFileSystemSearch extends RdiffFileSystem
{

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instances with search query applied
     *
     * @param array $params
     *
     * @return ArrayDataProvider[]
     */
    public function search($params)
    {

        $model = $params['model'];
        $date = array_key_exists('date', $params) ? $params['date'] : null;
        $path = array_key_exists('path', $params) ? $params['path'] : '/';

        $this->root = $model->exam->backup_path;
        $this->location = realpath(\Yii::$app->params['backupPath'] . '/' . $model->token);
        $this->restoreUser = 'root';
        $this->restoreHost = $model->ip;

        if ($date == null) {
            $date = ($model->state == Ticket::STATE_CLOSED || $model->state == Ticket::STATE_SUBMITTED)
                ? $this->newestBackupVersion
                : 'all';
        }

        if (file_exists(\Yii::$app->params['backupPath'] . '/' . $model->token)) {
            $models = $this->slash($path)->versionAt($date)->contents;
            $versions = $this->slash($path)->versions;
            array_unshift($versions , 'all');
        } else {
            $models = [];
            $versions = [];
        }

        $dataProvider = new ArrayDataProvider([
            'allModels' => $models,
            'pagination' => [
                'defaultPageSize' => 10,
                'pageSizeLimit' => [1, 100],
            ],
        ]);

        $VersionsDataProvider = new ArrayDataProvider([
            'allModels' => $versions,
        ]);

        return [$dataProvider, $VersionsDataProvider];
    }

}
