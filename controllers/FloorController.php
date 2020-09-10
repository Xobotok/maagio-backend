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

class FloorController extends BaseController
{
    public $enableCsrfValidation = false;
    public static function createFloor($user_id,$project_id, $floor) {
        $model = new Floors();
        $model->project_id = $project_id;
        $model->save();
        foreach($floor->units as $unit) {
            $unitCont = UnitController::createUnit((int)$model->id, $unit);
            if(isset($unit->image) && count($unit->image) > 0) {
                $image = ImageController::uploadImage($user_id, $project_id, 'unit', $unit->image, $floor->id,'', $unitCont->id);
                if(isset($image->id)) {
                    $unitCont->image_id = $image->id;
                    $unitCont->save();
                }
            }
        }
        return $model;

    }
    public static function uploadFloorImage($user_id, $project_id, $floor_id, $floor_image) {
        $model = new Floors();
        $model = $model->findOne($floor_id);
        $floor_image = ImageController::uploadImage($user_id, $project_id, 'floor', $floor_image, $floor_id);
        if(isset($floor_image->id)) {
            $model->image_id = $floor_image->id;
            $model->save();
        }
        return $model;
    }
    public function actionGetBuildings() {

    }
    public function actionUpload() {
        $data = (object)yii::$app->request->post();
        if( isset( $_POST['my_file_upload'] ) ){

        }
    }
}
