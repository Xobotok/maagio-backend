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

class TariffController extends BaseController
{
    public $enableCsrfValidation = false;
    public static function takeAllTariffs() {
        $stripe = new \Stripe\StripeClient(
            STRIPE_LIVE_KEY
        );
        $products = [];
        $prices =  $stripe->prices->all(['active' => true]);
        foreach ($prices->data as $price) {
            $product = $stripe->products->retrieve($price->product);
            if($product->active == true) {
                $product->price_obj = $price;
                $product->price = $price->unit_amount;
                $product->interval = $price->recurring->interval;
                $product->price_id = $price->id;
                $products[] = $product;
            }
        }
        return $products;
    }
    public function actionTakeEndTariff() {
        $data = (object)yii::$app->request->get();
        $result = (object)[];
        $authorisation = $this->checkAuthorisation($data->user['uid'], $data->token);
        if ($authorisation->ok === 0) {
            $result->ok = 0;
            $result->message = 'Authorisation failed';
            return json_encode($result);
        }
        $user = Users::findOne($data->user['uid']);
        $customer = PaymentController::takeStripeCustomer($user->uid);
        $result->subscribe = $customer->subscriptions->data[0];
        $end_time = $result->subscribe->current_period_end - strtotime(date("Y-m-d H:i:s"));
        $result->end_time = $end_time;
        $result->ok = 1;
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
