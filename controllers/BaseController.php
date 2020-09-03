<?php

namespace app\controllers;

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
    public function actionGetBuildings() {

    }
}
