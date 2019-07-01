<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use app\models\User;
use app\models\AuthItem;

/**
 * This is the model class for event streams.
 *
 * @property integer $id
 * @property string $data
 * @property string $event
 * @property float $generated_at
 * @property integer $priority A value representing the importance of the event. A value of 0
 * is the highest priority, thus the event will be sent, no matter what happens. A value >0 
 * indicates that the event has lower priority, and therefore will only be sent if 
 * [EventStream::maxEventsPerSecond] is not exceeded.
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
     * the 'user' array can also contain 'ALL' to address all users (multicast event), role is then ignored.
     */
    public $concerns = [];

    public $jsonData;
    public $retry; //TODO
    public $sent_at;
    public $debug;
    public $path = '/tmp/events';

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
     * @return void
     */
    public function generate()
    {
        $oldData = $this->data;
        $this->data = json_encode($oldData);
        $translate_data = $this->translate_data;
        $this->translate_data = json_encode($translate_data);
        $this->generated_at = microtime(true);
        $this->broadcast = (array_key_exists('users', $this->concerns) && in_array('ALL', $this->concerns['users']))
            ? 1 : 0;
        $this->save();

        //this part can be removed later
        if (basename($this->event) == '*') {
            $file = '/tmp/user/' . dirname($this->event) . '/' . 'ALL';
        }else{
            $file = '/tmp/user/' . $this->event;
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

}
