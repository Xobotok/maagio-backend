<?php

namespace app\controllers;
require_once 'Constants.php';

use app\models\Floors;
use app\models\Images;
use app\models\Payment;
use app\models\PaymentMethod;
use app\models\Subscribes;
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
   public static function takeStripeCustomer($user_id) {
       $stripe = new \Stripe\StripeClient(
           STRIPE_LIVE_KEY
       );
       \Stripe\Stripe::setApiKey(STRIPE_LIVE_KEY);
       $user = Users::findOne($user_id);
       if($user->stripe_customer_id != NULL) {
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
       return $customer;
   }
   public function actionCancelSubscribe() {
       $data = (object)yii::$app->request->get();
       $result = (object)[];

       $authorisation = $this->checkAuthorisation($data->user['uid'], $data->token);
       if ($authorisation->ok === 0) {
           $result->ok = 0;
           $result->message = 'Authorisation failed';
           return json_encode($result);
       }
       $stripe = new \Stripe\StripeClient(
           STRIPE_LIVE_KEY
       );
       $user = Users::findOne($data->user['uid']);
       $customer = PaymentController::takeStripeCustomer($user->uid);
       \Stripe\Stripe::setApiKey(STRIPE_LIVE_KEY);
       if(isset($customer->subscriptions->data[0])) {
           $subscription = \Stripe\Subscription::retrieve($customer->subscriptions->data[0]->id);
           \Stripe\Subscription::update($subscription->id, [
               'cancel_at_period_end' => true,
               'proration_behavior' => 'create_prorations',
               'items' => [
                   [
                       'id' => $subscription->items->data[0]->id,
                       'price' => DEFAULT_STRIPE_PRICE,
                   ],
               ],
           ]);
           $subscribe = \Stripe\Subscription::retrieve($customer->subscriptions->data[0]->id);
       } else {
           $subscribe = null;
       }
       $payments = $stripe->paymentMethods->all([
           'customer' => $customer->id,
           'type' => 'card',
       ]);
       $payments = $payments->data;
       foreach ($payments as $payment) {
           $pay = $stripe->paymentMethods->retrieve($payment->id);
           $pay->detach();
       }
       $user->save();
       $result->ok = 1;
       $result->subscribe = $subscribe;
       return json_encode($result);
   }
   public function actionSubscribe() {
       $data = (object)yii::$app->request->get();
       $result = (object)[];

       $authorisation = $this->checkAuthorisation($data->user['uid'], $data->token);
       if ($authorisation->ok === 0) {
           $result->ok = 0;
           $result->message = 'Authorisation failed';
           return json_encode($result);
       }
       $stripe = new \Stripe\StripeClient(
           STRIPE_LIVE_KEY
       );
       \Stripe\Stripe::setApiKey(STRIPE_LIVE_KEY);
       if((int)$data->card['year'] < 100) {
           (int)$data->card['year'] = (int)$data->card['year'] + 2000;
       }
       $user = Users::findOne($data->user['uid']);
       $customer = PaymentController::takeStripeCustomer($user->uid);
       if($customer->invoice_settings->default_payment_method == NULL) {
           try {
               $paymentMethod = $stripe->paymentMethods->create([
                   'type' => 'card',
                   'card' => [
                       'number' => $data->card['number'],
                       'exp_month' =>  (int)$data->card['month'],
                       'exp_year' =>  (int)$data->card['year'],
                       'cvc' => (string)$data->card['cvv'],
                   ],
               ]);
           } catch (\Stripe\Exception\CardException $e) {
               $result->ok = 0;
               $result->message = 'The card number or data is incorrect';
               $result->error_code = 'card';
               return json_encode($result);
           }
           $stripe->paymentMethods->attach(
               $paymentMethod->id,
               ['customer' => $customer->id]
           );
           $stripe->customers->update(
               $customer->id,
               ['invoice_settings' => ['default_payment_method' => $paymentMethod->id]]
           );
       }
       if(isset($customer->subscriptions->data[0])) {
           $subscription = \Stripe\Subscription::retrieve($customer->subscriptions->data[0]->id);
           \Stripe\Subscription::update($subscription->id, [
               'cancel_at_period_end' => false,
               'proration_behavior' => 'create_prorations',
               'items' => [
                   [
                       'id' => $subscription->items->data[0]->id,
                       'price' => DEFAULT_STRIPE_PRICE,
                   ],
               ],
           ]);
           $subscribe = \Stripe\Subscription::retrieve($customer->subscriptions->data[0]->id);;
       } else {
           $subscribe = \Stripe\Subscription::create([
               'customer' => $customer->id,
               'items' => [[
                   'price' => DEFAULT_STRIPE_PRICE,
               ]],
           ]);
       }
       $user->save();
       $result->ok = 1;
       $result->subscribe = $subscribe;
       return json_encode($result);
   }
}
