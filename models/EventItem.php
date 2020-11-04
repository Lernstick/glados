<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use app\models\User;
use app\models\AuthItem;
use yii\helpers\FileHelper;

/**
 * This is the model class for event streams.
 *
 * @property integer $id
 * @property string $data data payload
 * @property string $event
 * @property float $generated_at
 * @property integer $priority A value representing the importance of the event. Has no meaning anymore
 *
 */
class EventItem extends \yii\db\ActiveRecord
{

    /**
     * @var array Array containing the users id and role names of users who should get the event.
     * Format example: 
     *  [
     *      'users' => [12, 13, 14],
     *      'roles' => ['admin'],
     *  ]
     * the 'users' array can also contain 'ALL' to address all users (multicast event), 'roles' is then ignored.
     */
    public $concerns = [];

    public $jsonData;
    public $retry; //TODO
    public $sent_at;
    public $debug;
    public $path = '/tmp/events';
    public $trigger_attributes = [];

    /**
     * @var string path to the folder structure being monitored by inotify
     */
    public $inotifyDir;

    /**
     * @const priority constants
     */
    const PRIORITY_GUARANTEE = 0;
    const PRIORITY_LAZY = 1;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->inotifyDir = \Yii::$app->params['tmpPath'] . '/inotify/';
        $this->on(self::EVENT_BEFORE_DELETE, [$this, 'deleteEvent']);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'event';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['data'], 'required'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers() {
        return $this->hasMany(User::className(), ['id' => 'user_id'])
            ->viaTable('rel_event_user', ['event_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoles() {
        return $this->hasMany(AuthItem::className(), ['name' => 'role'])
            ->viaTable('rel_event_role', ['event_id' => 'id']);
    }

    /**
     * Generates the event.
     * [[data]] is converted to json and translated. If the data payload is too large
     * (it exceeds the column size in the database), it will be outsourced in a temporary
     * file and the data field will contain the absolute path (ex: "file:///path/to/file").
     * The needed files are touched, such that the [[EventStream]] triggers the events and
     * the database record is created.
     *
     * @return void
     */
    public function generate()
    {
        $this->validate();

        $origData = $this->data;
        $this->data = json_encode($origData);
        $translate_data = $this->translate_data;
        $this->translate_data = json_encode($translate_data);
        $this->generated_at = microtime(true);
        $this->broadcast = (array_key_exists('users', $this->concerns) && in_array('ALL', $this->concerns['users']))
            ? 1 : 0;

        /* Store the data in a file if the data payload too large for the database.
         * Can happen sometimes in the case of images. 
         */
        foreach ($this->tableSchema->columns as $key => $column) {
            if($column->name == "data") {
                //var_dump($this->event, strlen($this->data) > $column->size, strlen($this->data), $column->size);die();
                if (strlen($this->data) > $column->size) {
                    if (is_writable(\Yii::$app->params['tmpPath'])) {
                        $filename = FileHelper::normalizePath(\Yii::$app->params['tmpPath'] . "/event-" . md5($this->data));
                        file_put_contents($filename, $this->data);
                        $this->data = "file://" . $filename;
                    }
                }
            }
        }

        $this->save(false);

        /* This is for the form EventItemSend only.
         * Set the data value back to its original value such that
         * [[data]] still is a string.
         */
        $this->data = is_array($origData) ? $this->data : $origData;

        // @todo this part can be removed later
        if (basename($this->event) == '*') {
            $file = $this->inotifyDir . '/' . dirname($this->event) . '/' . 'ALL';
        } else {
            $file = $this->inotifyDir . '/' . $this->event;
        }
        $this->touchFile($file, $this->id);

        if (array_key_exists('users', $this->concerns)) {
            if (in_array('ALL', $this->concerns['users'])) {
                $file = $this->path . '/ALL/' . 
                    (basename($this->event) == '*' ? 
                        dirname($this->event) . '/' . 'ALL' : 
                        $this->event);
                $this->touchFile($file, $this->id);
                return;
            }

            foreach ($this->concerns['users'] as $user_id) {
                if (is_numeric($user_id)) {
                    if (($user = User::findOne($user_id)) !== null) {
                        $this->link('users', $user);
                    }
                    $file = $this->path . '/user/' . $user_id . '/' . 
                        (basename($this->event) == '*'
                            ? dirname($this->event) . '/' . 'ALL'
                            : $this->event);
                    $this->touchFile($file, $this->id);
                }
            }

        }

        if (array_key_exists('roles', $this->concerns)) {
            $roles = [];
            foreach ($this->concerns['roles'] as $role) {
                $roles = array_unique(array_merge($roles, $this->getRolesByPermission($role)));
            }
            foreach ($roles as $role) {
                if (($authItem = AuthItem::findOne($role)) !== null) {
                    $this->link('roles', $authItem);
                }
                $file = $this->path . '/role/' . $role . '/' . 
                    (basename($this->event) == '*'
                        ? dirname($this->event) . '/' . 'ALL'
                        : $this->event);
                $this->touchFile($file, $this->id);
            }
        }

    }

    public function touchFile($file, $eventId)
    {

        //create the directory (recursive)
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0740, true);
        }

        //create or modify the file
        return file_put_contents($file, $eventId);
    }


    /**
     * Returns all RBAC roles which have the specified permission
     *
     * @param string $perm the name of the permission
     * @return array permissions/roles
     */
    private function getRolesByPermission($perm){

        return array_intersect(
            array_merge(
                array($perm),
                $this->getPermissionsByPermission($perm)
            ),
            array_map(
                function($i){
                    return $i->name;
                },
                array_values(\Yii::$app->authManager->getRoles())
            )
        );
    }

    /**
     * Returns all RBAC permissions and roles which have the specified permission
     *
     * @param string $perm the name of the permission
     * @return array permissions/roles
     */
    private function getPermissionsByPermission($perm) {
        $roles = [ ];
        foreach (\Yii::$app->authManager->childrenList as $parent => $children) {
            if (in_array($perm, $children)) {
                $roles[] = $parent;
            }
        }
        foreach ($roles as $role) {
            $r = $this->getPermissionsByPermission($role);
            $roles = array_merge($roles, $r);
        }
        return array_unique($roles);
    }

    /**
     * When the event item is deleted
     * 
     * @return bool success or failure
     */
    public function deleteEvent()
    {
        // the the data payload was a file
        if (strpos($this->data, "file://") === 0) {
            $file = substr($this->data, strlen("file://"));
            if (is_readable($file)) {
                return @unlink($file);
            }
        }
        return true;
    }

}
