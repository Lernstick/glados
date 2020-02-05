<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use yii\widgets\ActiveForm;
use app\models\Exam;
use app\models\ExamSetting;
use app\models\ScreenCapture;

class ExamForm extends Model
{

    private $_exam;
    private $_screenCapture;
    private $_settings;

    public function rules()
    {
        return [
            [['Exam'], 'required'],
            [['ScreenCapture', 'ExamSettings'], 'safe'],
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

        if (!$this->saveSettings()) {
            $transaction->rollBack();
            return false;
        }

        $this->exam->link('screenCapture', $this->screenCapture);
        $transaction->commit();
        return true;
    }

    public function saveSettings() 
    {
        $keep = [];
        foreach ($this->examSettings as $setting) {
            $setting->exam_id = $this->exam->id;
            if (!$setting->save(false)) {
                return false;
            }
            if ($setting->detail->belongs_to !== null && $setting->belongs_to !== null) {
                if (array_key_exists($setting->belongs_to, $this->examSettings)) {
                    $setting->link('belongsTo', $this->examSettings[$setting->belongs_to]);
                }
            }
            $keep[] = $setting->id;
        }
        $query = ExamSetting::find()->andWhere(['exam_id' => $this->exam->id]);
        if ($keep) {
            $query->andWhere(['not in', 'exam_setting.id', $keep]);
        }
        foreach ($query->all() as $setting) {
            $setting->delete();
        }        
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

    public function getExamSettings()
    {
        if ($this->_settings === null) {
            $this->_settings = $this->exam->isNewRecord ? [] : $this->exam->settings;
        }
        return $this->_settings;
    }

    private function getExamSetting($key)
    {
        $setting = $key && strpos($key, 'new') === false ? ExamSetting::findOne($key) : false;
        if (!$setting) {
            $setting = new ExamSetting();
            $setting->loadDefaultValue();
        }
        return $setting;
    }

    public function setExamSettings($settings)
    {
        unset($settings['__id__']); // remove the hidden "new ExamSetting" row
        $this->_settings = [];
        foreach ($settings as $key => $setting) {
            if (is_array($setting)) {
                $this->_settings[$key] = $this->getExamSetting($key);
                $this->_settings[$key]->setAttributes($setting);
            } elseif ($setting instanceof ExamSetting) {
                $this->_settings[$setting->id] = $setting;
            }
        }
    }

    public function errorSummary($form)
    {
        return $form->errorSummary($this->getAllModels());
    }

    private function getAllModels()
    {
        $models = [
            'Exam' => $this->exam,
            'ScreenCapture' => $this->screenCapture,
        ];
        foreach ($this->examSettings as $id => $setting) {
            $models['ExamSetting.' . $id] = $this->examSettings[$id];
        }
        return $models;
    }

}