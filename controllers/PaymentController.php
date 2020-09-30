<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
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

class PaymentController extends BaseController
{
    public $enableCsrfValidation = false;
   public function actionPayment(){
       \Stripe\Stripe::setApiKey('sk_test_51GuBOGC3fcSx3Qt91L47QYvBDBUAmKw4Q1s6rzQkRi8cdPEYlF06OqLopShQok5td9nugw66JwpX3xW9bqb8xmZZ00vlTIf9Bg');
       $intent = \Stripe\PaymentIntent::create([
           'amount' => 1099,
           'currency' => 'usd',
           // Verify your integration in this guide by including this parameter
           'metadata' => ['integration_check' => 'accept_a_payment'],
       ]);
       return json_encode($intent);
   }
}
