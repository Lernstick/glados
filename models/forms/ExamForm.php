<?php

namespace app\models\forms;

use Yii;
use yii\base\Model;
use yii\widgets\ActiveForm;
use app\models\Exam;
use app\models\ExamSetting;

class ExamForm extends Model
{

    /**
     * @var Exam the exam model
     */
    private $_exam;

    /**
     * @var \yii\db\ActiveQuery|ExamSetting[] array of exam setting models
     */
    private $_settings;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['Exam'], 'required'],
            [['ExamSettings'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterValidate()
    {
        if (!Model::validateMultiple($this->getAllModels())) {
            $this->addError(null); // add an empty error to prevent saving
        }
        parent::afterValidate();
    }

    /**
     * @inheritdoc
     *
     * This fixes that when an exam is updated and all settings are removed, the post() data has
     * no ExamSettings property, therefore examSettings is never set. Here we set it manually for
     * this case, such that later in [[saveSettings()]] the absent settings can be removed.
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (array_key_exists("Exam", $values) && !array_key_exists("ExamSettings", $values) ) {
            $this->examSettings = [];
        }

        return parent::setAttributes($values, $safeOnly);
    }

    /**
     * This function will call save() on the Exam model and [[saveSettings()]] in a transaction.
     *
     * @return bool Whether the saving succeeded (i.e. no validation errors occurred).
     * @throws yii\db\Exception if the transaction is not active
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }
        $transaction = Yii::$app->db->beginTransaction();

        if (!$this->exam->save()) {
            $transaction->rollBack();
            return false;
        }

        if (!$this->saveSettings()) {
            $transaction->rollBack();
            return false;
        }

        $transaction->commit();
        return true;
    }

    /**
     * Save all settings given by post()
     *
     * @return bool Whether the saving of all models succeeded
     * @throws yii\base\InvalidCallException if the method is unable to link two models in link().
     */
    public function saveSettings() 
    {
        $keep = [];
        foreach ($this->examSettings as $setting) {
            $setting->exam_id = $this->exam->id;
            if ($setting->save(false) === false) {
                return false;
            }
            $keep[] = $setting->id;
        }
        foreach ($this->examSettings as $setting) {
            // create the relation in the database
            if ($setting->belongs_to !== null) {
                if (array_key_exists($setting->belongs_to, $this->examSettings)) {
                    $setting->link('belongsTo', $this->examSettings[$setting->belongs_to]);
                }
            }
        }

        // remove the settings that where not in the post() data
        $query = ExamSetting::find()->andWhere(['exam_id' => $this->exam->id]);
        if (!empty($keep)) {
            $query->andWhere(['not in', 'exam_setting.id', $keep]);
        }
        foreach ($query->all() as $setting) {
            if ($setting->delete() === false) {
                return false;
            }
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
    }

    /**
     * Gets a ExamSetting model from the database or creates a new one with default values
     *
     * @param string|integer $key the id of the ExamSetting model
     * @return ExamSetting
     */
    private function getExamSetting($key)
    {
        $setting = $key && strpos($key, 'new') === false ? ExamSetting::findOne($key) : false;
        if (!$setting) {
            $setting = new ExamSetting();
            $setting->loadDefaultValue();
        }
        return $setting;
    }

    public function getDefaultExamSettings()
    {
        return ExamSetting::find()->where(['exam_id' => null])->all();
    }

    /**
     * Getter for [[examSettings]].
     * Returns an array of ExamSetting models if they are already aggregated or an ActiveQuery
     * instance. The array can also be empty.
     *
     * @return \yii\db\ActiveQuery|ExamSetting[]
     */
    public function getExamSettings()
    {
        if ($this->_settings === null) {
            $this->_settings = $this->exam->isNewRecord ? [] : $this->exam->exam_setting;
        }
        return $this->_settings;
    }

    /**
     * Setter for [[examSettings]].
     * Sets the [[_settings]] to an array of app\models\ExamSetting models
     * @return void
     */
    public function setExamSettings($settings)
    {
        unset($settings['__id__']); // remove the hidden "new ExamSetting" row
        $this->_settings = [];
        foreach ($settings as $key => $setting) {
            if (is_array($setting)) {
                $this->_settings[$key] = $this->getExamSetting($key);
                $this->_settings[$key]->setAttributes($setting);
            } else if ($setting instanceof ExamSetting) {
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
        ];
        foreach ($this->examSettings as $id => $setting) {
            $models['ExamSetting.' . $id] = $this->examSettings[$id];
        }
        return $models;
    }

}