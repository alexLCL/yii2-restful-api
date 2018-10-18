<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2018/10/18
 * Time: 10:12
 */

namespace api\models;

use yii\db\ActiveRecord;

class Book extends ActiveRecord{
    public static function tableName()
    {
        return 'book';
    }
}