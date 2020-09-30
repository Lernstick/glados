<?php

namespace app\models\forms;

use Yii;
use app\models\EventStream;

/**
 * This is the form model class for event streams.
 *
 * @inheritdoc
 *
 */
class EventStreamListen extends EventStream
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['listenEvents'], 'required'],
            ['listenEvents', 'filter', 'filter' => function ($value) {
                $arr = explode(",", $value);
                $arr = array_map('trim', $arr);
                return implode(",", $arr);
            }],
        ];
    }
}
