<?php

namespace app\components;

use yii\db\Migration;
use app\models\Translation;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class BaseMigration
 */
class BaseMigration extends Migration
{

    /**
     * @var string the table refering to the translations
     */
    public $translationTable = 'translation';

    /**
     * @var array all languages that are present in the table [[translationTable]]
     */
    public $languages = [
        'de'
    ];

    /**
     * @var array all classes in app\models that inherit app\models\TranslatedActiveRecord
     * Classes that use the table [[translationTable]]. Find them by
     * ````bash
     * grep -r "getTranslatedFields()" .
     * ```
     */
    public $translatedActiveRecords = [
        'app\models\Activity',
        'app\models\ExamSettingAvail',
        'app\models\Setting',
        'app\models\Ticket',
    ];

    /**
     * @var boolean if true don't alter the database
     */
    public $dryrun = false;

    /**
     * Getter for all categories
     *
     * @param string $lang the LanguageID of the language 
     * @return array all categories
     */
    public function getCategories($lang)
    {
        $dir = \Yii::getAlias('@app/messages/' . $lang);
        $cat = glob($dir . '/*.php');
        return array_map(function($e){
            return basename($e, '.php');
        }, $cat);
    }

    /**
     * Updates the translation table with the newest translations.
     * This routine can be called at the end of safeUp()
     *
     * @return integer the number of changed table entries
     */
    public function updateTranslationTable()
    {
        $models = Translation::find()->where(['not', ['category' => null]])->all();
        $nr = count($models);

        $i = 1;
        foreach ($models as $model) {
            $record = [];
            foreach ($this->languages as $lang) {
                $translation = \Yii::t($model->category, $model->en, [], $lang);
                $record[$lang] = $translation;
            }
            echo "Table " . $this->translationTable . ": Migrating database record " . $i . "/" . $nr . "\r";
            if (!$this->dryrun) {
                $this->update($this->translationTable, $record, ['id' => $model->id]);
            }
            $i = $i + 1;
        }
        return $i;
    }

    /**
     * Cleans up old unused entries in the translation table by removing translation table 
     * entries that are not referenced.
     *
     * @return integer the number of deleted table entries
     */
    public function cleanTranslationTable()
    {
        $allIds = [];
        $keepIds = [];

        // loop over all classes that use [[TranslatedActiveRecords]]
        foreach ($this->translatedActiveRecords as $class) {
            $model = new $class();
            $fields = array_map(function($e){ return $e . '_id'; }, $model->translatedFields);

            // get distinct ids for all translated fields
            foreach ($fields as $field) {
                $query = new Query();
                $query->select($field)
                    ->distinct()
                    ->from($model->tableName());

                $data = $query->all();
                foreach ($data as $entry) {
                    array_push($keepIds, $entry[$field]);
                }
            }
        }
        $keepIds = array_unique($keepIds);

        // get all translation table ids
        $query = new Query();
        $query->select('id')
            ->from($this->translationTable);
        $data = $query->all();

        foreach ($data as $entry) {
            array_push($allIds, $entry['id']);
        }

        // get all ids that are in the translation table but not in the keepIds array
        $remIds = array_diff($allIds, $keepIds);
        //$weirdIds = array_diff($keepIds, $allIds);

        $i = 0;

        // remove all these ids from the translation table
        foreach ($remIds as $id) {
            echo "Table " . $this->translationTable . ": Removing database record with id " . $id . "\r";
            if (!$this->dryrun) {
                $this->delete($this->translationTable, ['id' => $id]);
            }
            $i = $i + 1;
        }
        return $i;
    }
}
