<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
use app\models\Maps;
use app\models\Projects;
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

class ProjectController extends BaseController
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

    public function actionSaveNewProject()
    {
        $result = (object)[];
        $floor_list = [];
        $floor_images = [];
        $data = (object)yii::$app->request->post();

        $user_id = (isset($data->user_id)) ? $data->user_id : 0;
        $token = (isset($data->token)) ? $data->token : 0;
        $authorisation = $this->checkAuthorisation($user_id, $token);
        if($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $project = json_decode($data->project);

        $model = new Projects();
        $model->user_id = $user_id;
        $model->name = $project->name;
        $model->save();
        if(count($_FILES) > 0) {
            $files = $_FILES;
            // ВАЖНО! тут должны быть все проверки безопасности передавемых файлов и вывести ошибки если нужн
            $uploaddir = '../image_storage/uploads'; // . - текущая папка где находится submit.php
            // cоздадим папку если её нет
            if( ! is_dir( $uploaddir ) ) mkdir( $uploaddir, 0777 );
            foreach ($files as $key => $file) {
                $findme   = 'floor';
                $pos = strpos($key, $findme);
                if($pos !== false) {
                    $floor_images[explode('floor-', $key)[1]] = $file;
                }
                $findme   = 'gallery';
                $pos = strpos($key, $findme);
                if($pos !== false) {
                    $gallery_images[explode('gallery-', $key)[1]] = $file;
                }
            }
        }
        if(!isset($model->id)) {
            $result->ok = 0;
            $result->error = 'The project cannot be saved';
            return $result;
        }
        $project_id = $model->id;

        if($project->map->lat != '' && $project->map->lng != '') {
            $model = new Maps();
            $model->project_id = $project_id;
            $model->lat = '' . $project->map->lat;
            $model->lng = '' . $project->map->lng;
            $model->save();
        }

        foreach ($project->floors as $key => $floor) {
            $model = new Floors();
            $model->project_id = $project_id;
            $model->save();
            foreach($floor->units as $unit) {
                $floor = new Units();
                $floor->floor_id = (int)$model->id;
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
            }
            if(!isset($model->id)) {
                $result->ok = 0;
                $result->error = 'The floors cannot be saved';
                return $result;
            }
            foreach ($floor_images as $key2 => $floor_image) {
                if($key === $key2) {
                    $user_folder = '../image_storage/uploads/user_' . $user_id;
                    if( ! is_dir( $user_folder ) ) mkdir( $user_folder, 0777 );
                    $project_folder = $user_folder . '/project_'.$project_id;
                    if( ! is_dir( $project_folder ) ) mkdir( $project_folder, 0777 );
                    $floor_folder = $project_folder . '/floors/';
                    if( ! is_dir( $floor_folder ) ) mkdir( $floor_folder, 0777 );
                    $file_name = $floor_image['size'].$floor_image['name'];
                    if( move_uploaded_file( $floor_image['tmp_name'], "$floor_folder/$file_name" ) ){
                        $done_files[] = realpath( "$floor_folder/$file_name" );
                        $image = new Images();
                        $image->image_link = realpath( "$floor_folder/$file_name" );
                        $image->image_name = $floor_image['name'];
                        $image->save();
                        $model->image_id = $image->id;
                        $model->save();
                    }
                }
            }
        }
        return var_dump($_FILES);
    }

}
