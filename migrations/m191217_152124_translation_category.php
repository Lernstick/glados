<?php

use yii\db\Migration;
use app\models\Translation;

/**
 * Class m191217_152124_translation_category
 */
class m191217_152124_translation_category extends Migration
{

    public $translationTable = 'translation';
    public $categories = [
        'setting',
        'activity',
        'exam_setting_avail',
        'ticket'
    ];
    public $lang = 'de';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn($this->translationTable, 'category', $this->string(64));
        $this->updateTranslationTable();
    }

    /**
     * Updates the translation table with new translations in german
     * @return void
     */
    public function updateTranslationTable()
    {
        $models = Translation::find()->all();
        $nr = Translation::find()->count();

        $messageSource = \Yii::$app->i18n->getMessageSource('*');

        $i = 1;
        foreach ($models as $model) {
            echo "Table " . $this->translationTable . " - " . $this->lang . ": Migrating database record " . $i . "/" . $nr . "\r";
            $en = $model->en;

            foreach ($this->categories as $category) {
                $path = Yii::getAlias($messageSource->basePath . '/' . $this->lang . '/' . $category . '.php');
                if (is_readable($path)) {
                    $messages = require($path);
                    if (array_key_exists($en, $messages)) {
                        $this->update($this->translationTable, [
                            // set the (new) translation
                            $this->lang => \Yii::t($category, $en, [], $this->lang),
                            // set the category
                            'category' => $category,
                        ], [
                            'en' => $en,
                        ]);

                        break;
                    }
                }
            }
            $i = $i + 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn($this->translationTable, 'category');
    }
}
