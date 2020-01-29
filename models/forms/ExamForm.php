<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use yii\widgets\ActiveForm;
use app\models\Exam;
use app\models\ScreenCapture;

class ExamForm extends Model
{

    private $_exam;
    private $_screenCapture;

    public function rules()
    {
        return [
            [['Exam'], 'required'],
            [['ScreenCapture'], 'safe'],
        ];
    }

    public function afterValidate()
    {
        if (!Model::validateMultiple($this->getAllModels())) {
            $this->addError(null); // add an empty error to prevent saving
        }
        parent::afterValidate();
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }
        $transaction = Yii::$app->db->beginTransaction();
        if (!$this->screenCapture->save()) {
            $transaction->rollBack();
            return false;
        }
        if (!$this->exam->save()) {
            $transaction->rollBack();
            return false;
        }
        $this->exam->link('screenCapture', $this->screenCapture);
        $transaction->commit();
        return true;
    }

    public function getExam()
    {
        return $this->_exam;
    }

    public function setExam($exam)
    {
        if ($exam instanceof Exam) {
            $this->_exam = $exam;
        } else if (is_array($exam)) {
            $this->_exam->setAttributes($exam);
        }

        if ($this->exam->screenCapture === null) {
            $this->_screenCapture = new ScreenCapture();
        } else {
            $this->_screenCapture = $this->exam->screenCapture;
        }
    }


    public function getScreenCapture()
    {
        return $this->_screenCapture;
    }

    public function setScreenCapture($screenCapture)
    {
        if ($screenCapture instanceof ScreenCapture) {
            $this->_screenCapture = $screenCapture;
        } else if (is_array($screenCapture)) {
            $this->_screenCapture->setAttributes($screenCapture);
        }
    }

    public function errorSummary($form)
    {
        $errorLists = [];
        foreach ($this->getAllModels() as $id => $model) {
            $errorList = $form->errorSummary($model, [
              'header' => '<p>Please fix the following errors for <b>' . $id . '</b></p>',
            ]);
            $errorList = str_replace('<li></li>', '', $errorList); // remove the empty error
            $errorLists[] = $errorList;
        }
        return implode('', $errorLists);
    }

    private function getAllModels()
    {
        $models = [
            'Exam' => $this->exam,
            'ScreenCapture' => $this->screenCapture,
        ];
        return $models;
    }

}