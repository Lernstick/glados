<?php

use yii\db\Migration;
use app\models\Exam;

/**
 * Class m190208_124133_two_files
 */
class m190208_124133_two_files extends Migration
{

    public $examTable = 'exam';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn($this->examTable, 'file2', $this->string(1024)->defaultValue(null));
        $models = Exam::find()->where(['like', 'file', '%zip', false])->all();
        foreach ($models as $model) {
            $model->file2 = $model->file;
            $model->file = null;
            $model->md5 = null;
            $model->update(false); // skipping validation as no user input is involved
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $models = Exam::find()->where(['like', 'file2', '%zip', false])->all();
        foreach ($models as $model) {
            $model->file = $model->file2;
            $model->file2 = null;
            $model->md5 = null;
            $model->{"file_analyzed"} = 0;
            $model->update(false); // skipping validation as no user input is involved
        }
        $this->dropColumn($this->examTable, 'file2');
    }
}
