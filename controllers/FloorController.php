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
    public static function createFloor($project_id, $floor) {
        $model = new Floors();
        $model->project_id = $project_id;
        $model->save();
        foreach($floor->units as $unit) {
            $floor = UnitController::createUnit((int)$model->id, $unit);
        }
        return $model;

    }
    public static function uploadFloorImage($user_id, $project_id, $floor_id, $floor_image) {
        $model = new Floors();
        $model = $model->findOne($floor_id);
        $user_folder = '../image_storage/uploads/user_' . $user_id;
        if( ! is_dir( $user_folder ) ) mkdir( $user_folder, 0777 );
        $project_folder = $user_folder . '/project_'.$project_id;
        if( ! is_dir( $project_folder ) ) mkdir( $project_folder, 0777 );
        $floor_folder = $project_folder . '/floors/';
        if( ! is_dir( $floor_folder ) ) mkdir( $floor_folder, 0777 );
        $file_name = $floor_id.'_'.$floor_image['name'];

        if( move_uploaded_file( $floor_image['tmp_name'], "$floor_folder/$file_name" ) ){
            $done_files[] = realpath( "$floor_folder/$file_name" );
            $image = new Images();
            $image->image_link = realpath( "$floor_folder/$file_name" );
            $image->name = $floor_image['name'];
            $image->save();
            $model->image_id = $image->id;
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
