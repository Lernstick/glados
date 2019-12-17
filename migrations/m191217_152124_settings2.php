<?php

use yii\db\Migration;
use app\models\Setting;

/**
 * Class m191217_152124_settings2
 */
class m191217_152124_settings2 extends Migration
{
    public $settingsTable = 'setting';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->addColumn($this->settingsTable, 'description_data', $this->string(1024)->defaultValue(null));
        $this->addColumn($this->settingsTable, 'description_id', $this->integer(11)->notNull());

        $tokenLength = new Setting([
            'key' => yiit('setting', 'Token length'),
            'type' => 'integer',
            'default_value' => 10,
            'description' => yiit('setting', 'The length of the token, when generated in number of characters.'),
        ]);
        $tokenLength->save(false);

        $loginHint = Setting::find()->where(['key' => 'Login hint'])->one();
        $loginHint->description = yiit('setting', 'A hint that is show in the login form for users attempting to login.');
        $loginHint->save();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Setting::deleteAll(['key' => 'Token length']);

        $this->dropColumn($this->settingsTable, 'description_data');
        $this->dropColumn($this->settingsTable, 'description_id');
    }
}
