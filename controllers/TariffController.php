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
                $product->price = $price->unit_amount;
                $product->interval = $price->recurring->interval;
                $product->price_id = $price->id;
                $products[] = $product;
            }
        }
        return $products;
    }
}
