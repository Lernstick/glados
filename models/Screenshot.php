<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\imagine\Image;

/**
 * This is the model class for screenshot.
 *
 * @property string $src
 * @property string $tsrc
 * @property string $thumbnail
 *
 */
class Screenshot extends Model
{

    /**
     * @var integer The date of the screenshot
     */
    public $date;

    /**
     * @var string The token from the related ticket
     */
    public $token;

    /**
     * @var string The filesystem path to the picture
     */
    public $path;

    /**
     * @var string
     */
    private $_thumbnail;

    const SCREENSHOTDIRS = [
        'Screenshots',
        '.Screenshots',
    ];

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['date', 'token'], 'required'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'date' => 'Date',
        ];
    }

    public function getSrc()
    {
        return Url::to([
            'screenshot/view',
            'date' => $this->date,
            'token' => $this->token,
        ]);
    }

    public function getTsrc()
    {
        return Url::to([
            'screenshot/view',
            'date' => $this->date,
            'token' => $this->token,
            'type' => 'thumb',
        ]);
    }

    /**
     * Return the first Screeshot directory that exists from the list.
     *
     * @param string $token the token
     * @return string the directory
     */
    public function getScreenshotDir($token)
    {
        foreach (self::SCREENSHOTDIRS as $key => $value) {
            $dir = \Yii::$app->params['backupPath'] . '/' . $token . '/' . $value . '/';
            if (is_dir($dir)) {
                return $value;
            }
        }
        return self::SCREENSHOTDIRS[0];
    }

    /**
     * Return the Screeshot model related to the token and the date
     *
     * @param string $token - token
     * @param string $date - date
     * @return Screenshot|null
     */
    public function findOne($token, $date)
    {
        $dir = \Yii::$app->params['backupPath'] . '/' . $token . '/' . self::getScreenshotDir($token) . '/';
        if (file_exists($dir)) {
            $files = scandir($dir, SCANDIR_SORT_DESCENDING);
            foreach ($files as $screenshot) {
                $path = $dir . '/' . $screenshot;
                if(@is_array(getimagesize($path)) && filemtime($path) == $date){
                    return new Screenshot([
                        'path' => $path,
                        'token' => $token,
                        'date' => $date,
                    ]);
                }
            }
        }

        return null;

    }

    /**
     * Returns all Screenshot models related to the token
     *
     * @param string $token - the token
     * @return Screenshot[]
     */
    public function findAll($token)
    {
        $dir = \Yii::$app->params['backupPath'] . '/' . $token . '/' . $this->getScreenshotDir($token) . '/';
        $models = [];

        if (file_exists($dir)) {
            $files = scandir($dir, SCANDIR_SORT_DESCENDING);
            foreach ($files as $screenshot) {
                $path = $dir . '/' . $screenshot;
                if(@is_array(getimagesize($path))){
                    $models[] = new Screenshot([
                        'path' => $path,
                        'token' => $token,
                        'date' => filemtime($path),
                    ]);
                }
            }
        }
        return $models;
    }

    /**
     * Generates a thumbnail of the screenshot if not yet created.
     * 
     * @return string - the path to the generated thumbnail
     */
    public function getThumbnail()
    {
        $dir = Yii::getAlias('@runtime/thumbnails/' . $this->token);
        $tpath = $dir . '/' . basename($this->path);
        if (!file_exists($tpath)) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }            
            Image::thumbnail($this->path, 250, null)
                ->save($tpath, ['quality' => 50]);
        }
        return $tpath;
    }

}
