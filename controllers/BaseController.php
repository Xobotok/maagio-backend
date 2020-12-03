<?php

namespace app\controllers;
require_once 'Constants.php';

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
    public static $PATH_TO_IMAGE = PATH_TO_IMAGE;
    public $enableCsrfValidation = false;
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                // restrict access to
                'Origin' => ['*'],
                // Allow  methods
                'Access-Control-Request-Method' => ['POST', 'PUT', 'OPTIONS', 'GET'],
                // Allow only headers 'X-Wsse'
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Headers' => ['Content-Type'],
                // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                //'Access-Control-Allow-Credentials' => true,
                // Allow OPTIONS caching
                'Access-Control-Max-Age' => 3600,
                // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                'Access-Control-Expose-Headers' => ['*'],
            ],
        ];
        return $behaviors;
    }
    public function beforeAction($action)
    {
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
    }

    public function getIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    function checkAuthorisation($uid, $token)
    {
        $model = new Users();
        $result = (object)[];
        $user = $model->find()->where(['uid' => (int)$uid, 'login_token' => $token])->one();
        if ($user === NULL) {
            $result->ok = 0;
            $result->user = $user;
            $result->error = 'Authorisation failed. Wrong user_id or token';
        } else {
            $ip = $this->getIp();
            $result->ok = 1;
            $result->ip = $ip;
            $result->message = 'Authorisation success';
        }
        return $result;
    }

    public function actionGetBuildings()
    {

    }
}
