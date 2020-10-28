<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
use app\models\Payment;
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

class PaymentController extends BaseController
{
    public $enableCsrfValidation = false;
   public function actionPayment(){
       $data = (object)yii::$app->request->get();
       $result = (object)[];

       $authorisation = $this->checkAuthorisation($data->user['uid'], $data->token);
       if ($authorisation->ok === 0) {
           $result->ok = 0;
           $result->message = 'Authorisation failed';
           return json_encode($result);
       }
       $stripe = new \Stripe\StripeClient(
           'sk_test_51GuBOGC3fcSx3Qt91L47QYvBDBUAmKw4Q1s6rzQkRi8cdPEYlF06OqLopShQok5td9nugw66JwpX3xW9bqb8xmZZ00vlTIf9Bg'
       );
       \Stripe\Stripe::setApiKey('sk_test_51GuBOGC3fcSx3Qt91L47QYvBDBUAmKw4Q1s6rzQkRi8cdPEYlF06OqLopShQok5td9nugw66JwpX3xW9bqb8xmZZ00vlTIf9Bg');
        $tariff = Tariff::findOne($data->tariff);
       $user = Users::findOne($data->user['uid']);
       if($user->stripe_customer_id != null) {

           try {
               $customer = $stripe->customers->retrieve(
                   $user->stripe_customer_id,
                   []
               );
           } catch(\Stripe\Exception\InvalidRequestException $e) {
               $customer = \Stripe\Customer::create([
                   'email' => $user->email,
                   ]);
               $user->stripe_customer_id = $customer->id;
               $user->save();
           }
       } else {
           $customer = \Stripe\Customer::create([
               'email' => $user->email,
           ]);
           $user->stripe_customer_id = $customer->id;
           $user->save();
       }
       if($customer->invoice_settings->default_payment_method != null ) {
           $intent = \Stripe\PaymentIntent::create([
               'amount' => ($tariff->price * 100),
               'currency' => 'usd',
               // Verify your integration in this guide by including this parameter
               'metadata' => ['integration_check' => 'accept_a_payment'],
               'customer' => $customer->id,
               'payment_method' => $customer->invoice_settings->default_payment_method
           ]);
       } else {
           $intent = \Stripe\PaymentIntent::create([
               'amount' => ($tariff->price * 100),
               'currency' => 'usd',
               // Verify your integration in this guide by including this parameter
               'metadata' => ['integration_check' => 'accept_a_payment'],
               'customer' => $customer->id,
           ]);
       }
       return json_encode($intent);
   }
    public function actionSuccess(){
        $data = (object)yii::$app->request->get();
        $result = (object)[];
        $authorisation = $this->checkAuthorisation($data->user['uid'], $data->token);
        if ($authorisation->ok === 0) {
            $result->ok = 0;
            $result->message = 'Authorisation failed';
            return json_encode($result);
        }
        if($data->paymentIntent['status'] == 'succeeded') {
            $user = Users::findOne($data->user['uid']);
            \Stripe\Stripe::setApiKey('sk_test_51GuBOGC3fcSx3Qt91L47QYvBDBUAmKw4Q1s6rzQkRi8cdPEYlF06OqLopShQok5td9nugw66JwpX3xW9bqb8xmZZ00vlTIf9Bg');
            $stripe = new \Stripe\StripeClient(
                'sk_test_51GuBOGC3fcSx3Qt91L47QYvBDBUAmKw4Q1s6rzQkRi8cdPEYlF06OqLopShQok5td9nugw66JwpX3xW9bqb8xmZZ00vlTIf9Bg'
            );
            $customer = $stripe->customers->retrieve(
                $user->stripe_customer_id,
                []
            );
            if($data->save_payment == true) {
                $stripe->customers->update(
                    $customer->id,
                    ['invoice_settings' => ['default_payment_method' => $data->paymentIntent['payment_method']]]
                );
            }
            $tariffs = Tariff::find()->asArray()->all();
            foreach ($tariffs as $tariff) {
                if($tariff['price'] * 100 == (int)$data->paymentIntent['amount']) {
                    $payment = Payment::find()->where(['stripe_id' => $data->paymentIntent['id']])->one();
                    if($payment == NULL) {
                        $user = Users::findOne($data->user['uid']);
                        $end_tariff = $user->end_tariff_date;
                        if($end_tariff == null || strtotime(date("Y-m-d H:i:s", strtotime($end_tariff))) < strtotime(date("Y-m-d H:i:s"))) {
                            $actual_date = date("Y-m-d H:i:s");
                        } else {
                            $actual_date = date("Y-m-d H:i:s", strtotime($user->end_tariff_date));
                        }
                        $payment = new Payment();
                        $payment->user_id = $data->user['uid'];
                        $payment->tariff_id = $tariff['id'];
                        $payment->payment_end = date("Y-m-d H:i:s", strtotime($actual_date) + 60*60*24*$tariff['period']);
                        $payment->stripe_id = $data->paymentIntent['id'];
                        $payment->save();
                        $user = Users::findOne($data->user['uid']);
                        $user->last_payment_id = $payment->id;
                        $user->end_tariff_date = $payment->payment_end;
                        $user->save();
                        $result->ok = 1;
                        $result->payment_end = strtotime($user->end_tariff_date) - strtotime(date("Y-m-d H:i:s"));
                        return json_encode($result);
                    }
                }
            }
        } else {
            $result->ok = 0;
            $result->message = 'Something went wrong';
            return json_encode($result);
        }
        return json_encode($result);
    }
    public function actionTakeUserTariffs() {
        $data = (object)yii::$app->request->get();
        $result = (object)[];
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            $result->ok = 0;
            $result->message = 'Authorisation failed';
            return json_encode($result);
        }
        $result = (object)[];
        $tariffs = Tariff::find()->where(['type' => 0])->asArray()->all();
        $result->tariffs = $tariffs;
        $trial = Tariff::find()->where(['type' => 1])->asArray()->all();
        foreach ($trial as $tariff) {
            $trial_flag = Payment::find()->where(['user_id' => $data->user_id, 'tariff_id' => $tariff['id']])->asArray()->all();
            if(count($trial_flag)  == 0) {
                array_unshift($result->tariffs,  $tariff);
            }
        }

        $result->ok = 1;

        return json_encode($result);
    }
}
