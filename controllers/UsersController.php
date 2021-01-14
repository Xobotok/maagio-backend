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
use Swift_Plugins_LoggerPlugin;
use Swift_Plugins_Loggers_ArrayLogger;
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
   public function actionChangePassword() {
       $data = (object)yii::$app->request->get();
       $result = (object)[];
       $user = Users::find()->where(['password_restore_token' => $data->token])->one();
       if($user == NULL) {
           $result->ok = 0;
           $result->message = 'The user is not found';
           return json_encode($result);
       }
       $user = Users::findOne($user->attributes['uid']);
       if($data->pass != '') {
           $user->password = md5($data->pass);
           $user->password_restore_token = null;
           $user->save();
           $result->ok = 1;
           $result->message = 'Password changed.';
           return json_encode($result);
       } else {
           $result->ok = 0;
           $result->message = 'Wrong password';
           return json_encode($result);
       }
   }
   public function actionRestorePassword() {
       $data = (object)yii::$app->request->get();
       $result = (object)[];
       $user = Users::find()->where(['email' => $data->email])->one();
       if($user == NULL) {
           $result->ok = 0;
           $result->message = 'The user is not found';
           return json_encode($result);
       }
       $user = Users::findOne($user->attributes['uid']);
       $user->password_restore_token = md5(time()) . $user->uid;
       $user->save();
       $logger = new Swift_Plugins_Loggers_ArrayLogger();
       $mailer = Yii::$app->get('mailer');
       $message  = Yii::$app->mailer->compose()
           ->setFrom(['no-reply@maggio.app' => 'Maagio restore password'])
           ->setTo($data->email)
           ->setSubject('Maagio restore password')
           ->setHtmlBody('<p>For restore your password click </p><b><a href="'.FRONTEND_URL.'/restore/?restore_token='.$user->password_restore_token.'">here</a></b>');
       $mailer->getSwiftMailer()->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
       try{
           $message->send();
       }   catch(\Swift_SwiftException $exception) {
           $result->ok = 0;
           $result->error = "Can't send activate email. Check your email address";
           $user->delete();
           return json_encode($result);
       }
       $result->ok = 1;
       $result->message = 'Check your email and follow the instructions';
       return json_encode($result);
   }
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
