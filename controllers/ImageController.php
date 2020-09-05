<?php

namespace app\controllers;

use app\models\Images;
use app\models\Users;
use MailSender;
use phpDocumentor\Reflection\Types\Null_;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class ImageController extends BaseController
{
    public $enableCsrfValidation = false;
    public static function allowedDomains() {
        return [
            '*',                        // star allows all domains
        ];
    }
    public static function createImage($name, $path) {
        $model = new Images();
        $model->name = $name;
    }
    public function actionIndex() {


    }
    public function actionGetBuildings() {

    }
    public function actionUpload() {
            $data = (object)yii::$app->request->post();
           if( isset( $_POST['my_file_upload'] ) ){

        }
    }
}
