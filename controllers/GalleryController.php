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
    public static function checkImage($photo) {
        $formats = ['image/jpg', 'image/jpeg', 'image/png'];
        $max_size = 10485760;
        $format_flag = false;
        $size_flag = false;
        $result = true;
        foreach ($formats as $format) {
            if($format === $photo['type']) {
                $format_flag = true;
            }
        }
        if((int)$photo['size'] <= $max_size) {
            $size_flag = true;
        }
        if(!$format_flag) {
            $result = [];
            $result['ok'] = 0;
            $result['message'] = 'Wrong file format. Can upload only png, jpeg and jpg';
            return $result;
        }
        if(!$size_flag) {
            $result = [];
            $result['ok'] = 0;
            $result['message'] = 'File is too big. Max size 10MB';
            return $result;
        }
        return $result;
    }
    public static function uploadPhotos($user_id, $project_id, $gallery_id, $photos_array) {
        foreach ($photos_array as $photo) {
            $checkFile = GalleryController::checkImage($photo);
            if($checkFile !== true){return $checkFile;};
            $user_folder = '../image_storage/uploads/user_' . $user_id;
            if( ! is_dir( $user_folder ) ) mkdir( $user_folder, 0777 );
            $project_folder = $user_folder . '/project_'.$project_id;
            if( ! is_dir( $project_folder ) ) mkdir( $project_folder, 0777 );
            $gallery_folder = $project_folder . '/gallery_'.$gallery_id.'/';
            if( ! is_dir( $gallery_folder ) ) mkdir( $gallery_folder, 0777 );
            $file_name = $photo['size'].'_'.$photo['name'];
            if( move_uploaded_file( $photo['tmp_name'], "$gallery_folder/$file_name" ) ){
                $done_files[] = realpath( "$gallery_folder/$file_name" );
                $image = new Images();
                $image->image_link = realpath( "$gallery_folder/$file_name" );
                $image->name = $photo['name'];
                $image->save();
                if(isset($image->id)) {
                    $model = new GalleryPhotos();
                    $model->gallery_id = $gallery_id;
                    $model->image_id = $image->id;
                    $model->number = $photo['number'];
                    $model->save();
                }
            }
        }
    }
    public function actionGetBuildings() {

    }
    public function actionUpload() {
        $data = (object)yii::$app->request->post();
        if( isset( $_POST['my_file_upload'] ) ){

        }
    }
}
