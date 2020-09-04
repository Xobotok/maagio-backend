<?php

namespace app\controllers;

use app\models\Users;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public function beforeAction($action)
    {
        return parent::beforeAction($action);
    }
    public function actionIndex() {


    }
     function checkAuthorisation($uid, $token) {
        $model = new Users();
        $result = (object)[];
        if($model->find()->where(['uid'=>$uid, 'login_token' => $token])->one() === NULL) {
            $result->ok = 0;
            $result->error = 'Authorisation failed. Wrong user_id or token';
        } else {
            $result->ok = 1;
            $result->message = 'Authorisation success';
        }
        return $result;
    }
    public function actionGetBuildings() {

    }
}
