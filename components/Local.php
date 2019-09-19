<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Local represents a local authentication via db
 *
 */
class Local extends \app\models\Auth
{

    /**
     * @inheritdoc
     */
    public $id = 'init';

    /**
     * @inheritdoc
     */
    public $class = 'app\components\Local';

    /**
     * @inheritdoc
     */
    public $type = \app\models\Auth::LOCAL;

    /**
     * @inheritdoc
     */
    public $typeName = 'DB';

    /**
     * @inheritdoc
     */
    public $name = 'Local';

    /**
     * @inheritdoc
     */
    public $description = 'Local Database Authentication';

    /**
     * @inheritdoc
     */
    public $loginScheme = '{username}';

    /**
     * @inheritdoc
     */
    public $view = 'view_local';

    /**
     * @inheritdoc
     */
    public $order = 0;

    /**
     * The explanation of the local authentication method.
     * @return string the explanation
     */
    public function getExplanation ()
    {
        return Yii::t('auth', 'The local authentication method <b>cannot</b> be changed or deleted. It will always be set up as first configuration item with order 0. All login attempts will first be authenticated through this local method. This cannot be changed. If the authentication fails via local database in any way (username does not exist / password is wrong), the authentication method list will be processed further in the given order.');
    }

}