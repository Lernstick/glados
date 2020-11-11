<?php

use app\components\BaseMigration;

# dummy migration to mark version 1.0.8
class m201009_144208_v1_0_8 extends BaseMigration
{

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // updates the translation table with the newest translations
        $this->updateTranslationTable();

        // removes translation table entries that are not referenced
        $this->cleanTranslationTable();

        echo "db version 1.0.8 installed\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return true;
    }
}
