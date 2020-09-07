<?php

namespace app\controllers;

use app\models\Images;
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
/*const PATH_TO_IMAGE = 'http://a0466733.xsph.ru/image_storage/uploads/';*/
const PATH_TO_IMAGE = 'http://web/image_storage/uploads/';

class ImageController extends BaseController
{
    public $enableCsrfValidation = false;
    public static function allowedDomains() {
        return [
            '*',                        // star allows all domains
        ];
    }
    public static function createImage($name, $path) {
        $model = new Images();
        $model->name = $name;
    }
    public function actionIndex() {


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
    public static function uploadImage($user_id, $project_id, $type, $image, $floor_id = '', $gallery_id = '') {
        $checkImage = ImageController::checkImage($image);
        if($checkImage != true) {
            return $checkImage;
        }
        // ВАЖНО! тут должны быть все проверки безопасности передавемых файлов и вывести ошибки если нужн
        $uploaddir = './image_storage/'; // . - текущая папка где находится submit.php
        // cоздадим папку если её нет
        if( ! is_dir( $uploaddir ) ) mkdir( $uploaddir, 0777 );
        $uploaddir = './image_storage/uploads'; // . - текущая папка где находится submit.php
        // cоздадим папку если её нет
        if( ! is_dir( $uploaddir ) ) mkdir( $uploaddir, 0777 );
        if($type === 'logo') {
            $file_name = $user_id . '_' .$project_id . '_logo_' . $image['size'].$image['name'];
        } else if($type === 'floor') {
            $file_name = $user_id . '_' .$project_id . '_floor_'.$floor_id . '_' . $image['size'].$image['name'];
        } else if($type === 'gallery_image') {
            $file_name = $user_id . '_' .$project_id . '_gallery_'.$gallery_id . '_' . $image['size'].$image['name'];
        } else {
            return false;
        }

        if( move_uploaded_file( $image['tmp_name'], "$uploaddir/$file_name" ) ){
            $done_files[] = realpath( "$uploaddir/$file_name" );
            $image_model = new Images();
            $image_model->image_link = PATH_TO_IMAGE .$file_name;
            $image_model->name = $image['name'];
            $image_model->save();
            return $image_model;
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