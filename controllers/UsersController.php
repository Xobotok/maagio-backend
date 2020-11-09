<?php

namespace app\controllers;
require_once 'Constants.php';

use app\models\Floors;
use app\models\Images;
use app\models\Payment;
use app\models\PaymentMethod;
use app\models\Tariff;
use app\models\Units;
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

class UsersController extends BaseController
{
    public $enableCsrfValidation = false;
   public function actionSaveProfile() {
       $data = (object)yii::$app->request->get();
       $result = (object)[];

       $authorisation = $this->checkAuthorisation($data->user['uid'], $data->token);
       if ($authorisation->ok === 0) {
           $result->ok = 0;
           $result->message = 'Authorisation failed';
           return json_encode($result);
       }
       $user = Users::findOne($data->user['uid']);
       $user->first_name = $data->name;
       $user->last_name = $data->last_name;
       $user->company = $data->company_name;
       $user->save();
       $result->ok = 1;
       $result->user = (object)['name' =>$user->first_name, 'last_name' => $user->last_name, 'company' => $user->company, 'email' => $user->email];;
       return json_encode($result);
   }
}
