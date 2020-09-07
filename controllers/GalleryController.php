<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Galleries;
use app\models\GalleryPhotos;
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

class GalleryController extends BaseController
{
    public $enableCsrfValidation = false;
    public static function createGallery($project_id, $name) {
        $model = new Galleries();
        $model->name = ''.$name;
        $model->project_id = (int)$project_id;
        $model->save();
        return $model;
    }

    public static function uploadPhotos($user_id, $project_id, $gallery_id, $photos_array) {
        $result = (object)[];
        $result->failed_uploaded = [];
        $number = 0;
        foreach ($photos_array as $photo) {
            $image = ImageController::uploadImage($user_id, $project_id, 'gallery_image',$photo, '', $gallery_id);
            if(!$image->id){
                $result->failed_uploaded[] = $image->name;
            } else {
                $model = new GalleryPhotos();
                $model->image_id = $image->id;
                $model->gallery_id = $gallery_id;
                $model->number = $number;
                $model->save();
            }
            $number++;
        }
        return $result;
    }
    public function actionGetBuildings() {

    }
    public function actionUpload() {
        $data = (object)yii::$app->request->post();
        if( isset( $_POST['my_file_upload'] ) ){

        }
    }
}
