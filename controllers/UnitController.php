<?php

namespace app\controllers;

use app\models\Floors;
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

class UnitController extends BaseController
{
    public $enableCsrfValidation = false;
    public static function createUnit($floor_id, $unit) {
            $floor = new Units();
            $floor->floor_id = (int)$floor_id;
            $floor->unit_number = (int)$unit->unitNumber;
            $floor->bad = (int)$unit->bedroom;
            $floor->bath = (int)$unit->bathroom;
            $floor->price = (int)$unit->price;
            $floor->status = (int)$unit->status;
            if($unit->HOA != '') {
                $floor->hoa = (int)$unit->HOA;
            }
            $floor->int_sq = (int)$unit->interiorFootage;
            if($unit->exteriorFootage != '') {
                $floor->ext_sq = (int)$unit->exteriorFootage;
            }
            $floor->bmr = (int)$unit->bmr;
            $floor->parking = (int)$unit->parking;
            if($unit->imagePoint->X != '') {
                $floor->mark_x = '' . $unit->imagePoint->X;
            }
            if($unit->imagePoint->Y != '') {
                $floor->mark_y = '' . $unit->imagePoint->Y;
            }

            $floor->save();
            return $floor;
    }
    public function actionGetBuildings() {

    }
    public function actionUpload() {
        $data = (object)yii::$app->request->post();
        if( isset( $_POST['my_file_upload'] ) ){

        }
    }
}
