<?php

namespace app\models;

use Yii;
use app\models\Translation;
use yii\helpers\Json;

/**
 * This is the model for translated fields in ActiveRecord's
 */
class TranslatedActiveRecord extends Base
{

    /**
     * in joinWith() eagerLoading must be set to false, else an update will fail
     * because of https://github.com/yiisoft/yii2/blob/master/framework/validators/UniqueValidator.php#L198
     * In this line there is still $this->joinWith != null, but later
     * in L193 a select is overwritten only with the id, without the join fields.
     */
    const EAGERLOADING = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // For each translated db field, such an event needs to be fired
        foreach ($this->translatedFields as $key => $field) {
            $this->on(self::EVENT_BEFORE_INSERT, [$this, 'updateTranslation'], $field);
            $this->on(self::EVENT_BEFORE_UPDATE, [$this, 'updateTranslation'], $field);
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        $parts = explode('_', $name);
        $last = array_pop($parts);
        $pname = implode('_', $parts);

        // return the translated value with data if the property is read directly:
        // echo $this->name;
        if (in_array($name, $this->getTranslatedFields())) {
            // format() is like \Yii::t() but without translation
            return \yii\i18n\I18N::format($this->{$name . '_db'}, $this->{$name . '_params'}, 'en');
        }

        if ($last == 'params' && !empty($pname) && in_array($pname, $this->translatedFields)) {
            return $this->{$pname . "_data"} === null ? [] : Json::decode($this->{$pname . "_data"});
        } else if ($last == 'translation' && !empty($pname) && in_array($pname, $this->translatedFields)) {
            return $this->hasOne(Translation::className(), ['id' => $pname . '_id']);
        } else {
            return parent::__get($name);
        }
    }

    /**
     * Setter for the data. Format is as follows:
     * @see https://www.yiiframework.com/doc/guide/2.0/en/tutorial-i18n#message-parameters
     *
     *  [
     *      'key_1' => 'value_1'
     *      'key_2' => 'value_2'
     *      ...
     *      'key_n' => 'value_n'
     *  ]
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $parts = explode('_', $name);
        $last = array_pop($parts);
        $pname = implode('_', $parts);

        // set the db property if the property is set directly:
        // $this->name = $value;
        if (in_array($name, $this->getTranslatedFields())) {
            return $this->{$name . "_db"} = $value;
        }

        if ($last == 'params' && !empty($pname) && in_array($pname, $this->translatedFields)) {

            # get the size of the column from the table schema, for example int(1024) for varchar(1024)
            $maxSize = $this->tableSchema->columns[$pname . "_data"]->size;
            $size = strlen(Json::encode($value));

            if ($size > $maxSize) {

                $n = 0;
                foreach ($value as $k => $v) {
                    if (strlen($v) > $size/count($value)) {
                        $n++;
                    }
                }
                if ($n == 0) {
                    $n = 1;
                }

                # cut off too long strings
                $toCut = ceil(($size - $maxSize)/$n);
                foreach ($value as $k => $v) {
                    if (strlen($v) > $size/3) {
                        $value[$k] = substr($value[$k], 0, -($toCut+3)) . '...';
                    }
                }
            }
            return $this->{$pname . "_data"} = Json::encode($value);
        } else {
            return parent::__set($name, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        $parts = explode('_', $name);
        $last = array_pop($parts);
        $pname = implode('_', $parts);
        $prefix = "get";

        if (substr($pname, 0, strlen($prefix)) == $prefix) {
            $pname = substr($pname, strlen($prefix));
            if ($last == 'params' && !empty($pname) && in_array($pname, $this->translatedFields)) {
                return $this->{$pname . "_data"} === null ? [] : Json::decode($this->{$pname . "_data"});
            } else if ($last == 'translation' && !empty($pname) && in_array($pname, $this->translatedFields)) {
                return $this->hasOne(Translation::className(), ['id' => $pname . '_id']);
            } else {
                return parent::__call($name, $params);
            }
        } else {
            return parent::__call($name, $params);
        }
    }

    /**
     * For each translated field the database should have corresponding
     * reference fields, for example: if the field "description" should 
     * be translated, the original table must have the following columns:
     *   - description_id with data type integer(11)->notNull()
     *   - description_data with data type string(1024)->defaultValue(null)
     *
     * The following methods/properties will be created automatically:
     *   - obj->getDescription():
     *          @return string Returns the translated string including the data
     *   - obj->setDescription($value):
     *          @param string $value The string in sourceLanguage with placeholders
     *          @return void
     *   - obj->getDescription_params()
     *          @return array Array to set corresponding json value in description_data in the database
     *   - obj->setDescription_params($value)
     *          @param array Array of key value pair to replace the placeholders with
     *          @return void
     *   - obj->getDescription_translation @return \yii\db\ActiveQuery The relation to the translaton table
     *   - obj->getDescription_db @return string The string from directly from the database
     */
    public function getTranslatedFields()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function joinTranslationTables()
    {
        return array_map(function($value) {
            return $value . "_translation " . $value;
        }, self::classname()::getTranslatedFields());
    }

    /**
     * Automatic insertion/update of the data in the translation table
     *
     * @return void
     */
    public function updateTranslation($event)
    {
        if ($event->data !== null) {
            $field = $event->data;
            $category = self::classname()::tableName();

            $keys = array_keys($this->{$field . '_params'});
            $vals = array_map(function ($e) {
                return '{' . $e . '}';
            }, $keys);
            $params = array_combine($keys, $vals);

            $tr = Translation::find()->where([
                'en' => \Yii::t($category, $this->{$field . '_db'}, $params, 'en')
            ])->one();
            
            if ($tr === null || $tr === false) {
                // TODO: loop through all languages
                $translation = new Translation([
                    'en' => \Yii::t($category, $this->{$field . '_db'}, $params, 'en'),
                    'de' => \Yii::t($category, $this->{$field . '_db'}, $params, 'de'),
                ]);
                $translation->save();
                $this->{$field . '_id'} = $translation->id;
            } else {
                $this->{$field . '_id'} = $tr->id;
            }
        }
    }

}
