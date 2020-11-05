<?php

namespace app\controllers;
require_once 'Constants.php';

use app\models\Tariff;
use app\models\Users;
use MailSender;
use phpDocumentor\Reflection\Types\Null_;
use Swift_Plugins_LoggerPlugin;
use Swift_Plugins_Loggers_ArrayLogger;
use Yii;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class AuthorisationController extends BaseController
{
    public $enableCsrfValidation = false;
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
            ],
        ];
    }
    public function actionIndex() {


    }
    public function actionTakeUserInfo() {
        $data = (object)yii::$app->request->get();
        $result = (object)[];
        $authorisation = $this->checkAuthorisation($data->user['uid'], $data->token);
        if ($authorisation->ok === 0) {
            $result->ok = 0;
            $result->message = 'Authorisation failed';
            return json_encode($result);
        }
        $user = Users::findOne($data->user['uid']);
        if($user->end_trial_date != null) {
            $end_time = strtotime($user->end_trial_date) - strtotime(date("Y-m-d H:i:s"));
        } else {
            $end_time = 0;
        }
        $tariffs =  TariffController::takeAllTariffs();
        $result->end_time =$end_time;
        $result->subscribe_id = $user->subscribe_id;
        $result->tariffs = $tariffs;
        $result->actual_tariff = $tariffs[0];
        $result->ok = 1;
        return json_encode($result);
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
        if($user->end_trial_date != null) {
            $end_time = strtotime($user->end_trial_date) - strtotime(date("Y-m-d H:i:s"));
        } else {
            $end_time = 0;
        }
        $result->end_time =$end_time;
        $result->ok = 1;
        return json_encode($result);
    }
    public function actionGetBuildings() {

    }
    public function actionRegister() {
        $data = (object)yii::$app->request->get();
        $result = (object)[];
        $model = new Users();
        $check = $model->findOne(['email' => $data->email]);
        if($check != NULL) {
            $result->ok = 0;
            $result->error = 'Email already exist';
        } else {
            $user = new Users();
            $user->loadDefaultValues();
            if($data->email != '') {
                $user->email = $data->email;
            }
            if($data->name != '') {
                $user->first_name = $data->name;
            }
            if($data->last_name != '') {
                $user->last_name = $data->last_name;
            }
            if($data->company != '') {
                $user->company = $data->company;
            }
            if($data->password != '') {
                $user->password = md5($data->password);
            }
            $user->last_sign_in_at = date('Y-m-d H:i:s');
            $user->confirmation_sent_at = date('Y-m-d H:i:s');
            $user->confirmation_token = md5($data->email) . time();
            $logger = new Swift_Plugins_Loggers_ArrayLogger();
            $mailer = Yii::$app->get('mailer');
            $message  = Yii::$app->mailer->compose()
                ->setFrom(['no-reply@maggio.app' => 'Maagio account confirm'])
                ->setTo($user->email)
                ->setSubject('Welcome to Maagio')
                ->setHtmlBody('<p>For activate your Maagio Account click </p><b><a href="http://hareapj.cluster051.hosting.ovh.net/#/confirm/?confirm_token='.$user->confirmation_token.'">here</a></b>');
            $mailer->getSwiftMailer()->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
            try{
                $message->send();
            }   catch(\Swift_SwiftException $exception) {
                $result->ok = 0;
                $result->error = "Can't send activate email. Check your email address";
                $user->delete();
                return json_encode($result);
            }
            $user->save();

            $result->ok = 1;
            $result->message = 'Confirm your email address and log in';
        }
        return json_encode($result);
    }
    public function actionTest() {
        return var_dump(AuthorisationController::checkSubscribe(1));
    }
    public static function checkSubscribe($user_id) {
        $customer = PaymentController::takeStripeCustomer($user_id);
        $subscriptions = $customer->subscriptions->data;
        return var_dump($subscriptions);
        if($subscriptions != NULL && count($subscriptions) > 0) {
            return true;
        } else {
            return false;
        }
    }
    public function actionLogin() {
        $data = (object)yii::$app->request->get();
        $model = new Users();
        $result = (object)[];
        $user = $model->findOne(['email' => $data->email, 'password' => md5($data->password), 'confirmed' => 1]);
        $ip = $this->getIp();
        if($user != NULL) {
            $stripe = new \Stripe\StripeClient(
                STRIPE_LIVE_KEY
            );
            \Stripe\Stripe::setApiKey(STRIPE_LIVE_KEY);
            $user->login_token = md5($data->email . time());
            $user->last_sign_in_at =  date('Y-m-d H:i:s');
            $user->confirmation_sent_at =  date('Y-m-d H:i:s');
            $user->ip = $ip;
            if($user->stripe_customer_id != null){
                try {
                    $customer = $stripe->customers->retrieve($user->stripe_customer_id, []);

                } catch (\Stripe\Exception\InvalidRequestException $n) {
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

            if($user->end_trial_date == null) {
                $subscription = \Stripe\Subscription::create([
                    'customer' => $user->stripe_customer_id,
                    'items' => [
                        [
                            'price' => 'price_1Hk4MbC3fcSx3Qt9gx1kGMVS',
                        ],
                    ],
                    'trial_end' => strtotime(date('Y-m-d H:i:s'))  + 60 * 60 * 24 * 90,
                ]);
                $user->end_trial_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + 60 * 60 * 24 * 90);
            }
            $user->save();
            $result->ok = 1;
            $result->user = (object)[];
            $result->user->name = $user->first_name;
            $result->user->last_name = $user->last_name;
            $result->user->email = $user->email;
            $result->user->uid = $user->uid;
            $result->user->company = $user->company;
            $result->token = $user->login_token;
            $result->user->ip = $user->ip;
            $result->user->subscribe_id = $user->subscribe_id;
            $result->user->paid = strtotime($user->end_trial_date);
        } else {
            $user = $model->findOne(['email' => $data->email, 'password' => md5($data->password)]);
            if($user != NULL) {
                $result->ok = 0;
                $result->message = 'You need to activate your account. Follow the instructions sent to your email address';
            } else {
                $result->ok = 0;
                $result->message = 'Wrong email or password';
            }
        }
        return json_encode($result);
    }
    public function actionConfirm() {
        $data = (object)yii::$app->request->get();
        $token = $data->confirm_token;
        $model = new Users();
        $result = (object)[];
        $user = $model->findOne(['confirmation_token' => $token]);
        if($user != NULL) {
            $user->confirmed = 1;
            $user->confirmation_token = '';
            $user->save();
            $result->ok = 1;
            $result->message = 'Your account confirmed.';
        } else {
            $result->ok = 0;
            $result->message = "Account doesn't find.";
        }

        return json_encode($result);
    }

}
