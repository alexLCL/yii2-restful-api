<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2018/10/17
 * Time: 17:15
 */
namespace api\controllers;
use common\models\User;
use yii\rest\ActiveController;

class UserController extends ActiveController{
    public $modelClass='common\models\User';

    public function actions()
    {
        $action= parent::actions(); // TODO: Change the autogenerated stub
        unset($action['index']);
        unset($action['create']);
        unset($action['update']);
        unset($action['delete']);
    }

    public function actionSend()
    {
        return 2323;
    }
}