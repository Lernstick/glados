<?php

use yii\db\Migration;

/**
 * Class m190607_101830_libreoffice_paths
 */
class m190607_101830_libreoffice_paths extends Migration
{

    public $examTable = 'exam';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn($this->examTable, 'sq_url_whitelist', $this->string(512)->Null()->defaultValue(Null));
        $this->addColumn($this->examTable, 'libre_autosave_path', $this->string(255)->notNull()->defaultValue('/home/user/.config/libreoffice/4/user/tmp'));
        $this->addColumn($this->examTable, 'libre_createbackup_path', $this->string(255)->notNull()->defaultValue('/home/user/.config/libreoffice/4/user/backup'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn($this->examTable, 'libre_autosave_path');
        $this->dropColumn($this->examTable, 'libre_createbackup_path');
        $this->alterColumn($this->examTable, 'sq_url_whitelist', $this->string(5120)->Null()->defaultValue(Null));
    }
}
