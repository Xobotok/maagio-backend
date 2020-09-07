<?php

namespace app\controllers;

use app\models\Floors;
use app\models\GalleryPhotos;
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
    public static function createProject($user_id, $project) {
        $model = new Projects();
        $model->user_id = $user_id;
        $model->name = $project->name;
        $model->save();
        return $model;
    }
    public function actionGetUserProjects() {
        $data = (object)yii::$app->request->get();
        $result = (object)[];
        $model = new Users();
        $user = $model->find()->where(['uid' => $data->user_id, 'login_token' => $data->token])->one();
        if($user !== NULL) {
            $model = new Projects();
            $projects = $model->find()->where(['user_id' => $data->user_id])->asArray()->all();
            foreach ($projects as $key => $project) {
                $model = new Images();
                $model = $model->find()->where(['id'=>$project['project_logo']])->asArray()->one();
                if($model !== NULL) {
                    $projects[$key]['project_logo'] = $model['image_link'];
                }

            }
            $result->ok = 1;
            $result->data = $projects;
        } else {
            $result->ok = 0;
            $result->error = 'User not found';
        }
        return json_encode($result);
    }
    public function actionSaveNewProject()
    {
        $result = (object)[];
        $floor_images = [];
        $gallery_images = [];
        $data = (object)yii::$app->request->post();

        $user_id = (isset($data->user_id)) ? $data->user_id : 0;
        $token = (isset($data->token)) ? $data->token : 0;
        $authorisation = $this->checkAuthorisation($user_id, $token);
        if($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $project = json_decode($data->project);
        $project_model = self::createProject($user_id, $project);
        if(count($_FILES) > 0) {
            $files = $_FILES;
            foreach ($files as $key => $file) {
                $findme   = 'floor';
                $pos = strpos($key, $findme);
                if($pos !== false) {
                    $floor_images[explode('floor-', $key)[1]] = $file;
                }
                $findme   = 'gallery';
                $pos = strpos($key, $findme);
                if($pos !== false) {
                    $gallery_number = explode('_', explode('gallery-', $key)[1])[0];
                    $photo_number = explode('_', explode('gallery-', $key)[1])[1];
                    $gallery_images[$gallery_number][$photo_number] = $file;
                }
            }
        }

        if(!isset($project_model->id)) {
            $result->ok = 0;
            $result->error = 'The project cannot be saved';
            return $result;
        }
        $project_id = $project_model->id;

        if($project->map->lat != '' && $project->map->lng != '') {
            $model = new Maps();
            $model->project_id = $project_id;
            $model->lat = '' . $project->map->lat;
            $model->lng = '' . $project->map->lng;
            $model->save();
        }

        foreach ($project->floors as $key => $floor) {
           $model = FloorController::createFloor($project_id, $floor);
            if(!isset($model->id)) {
                $result->ok = 0;
                $result->error = 'The floors cannot be saved';
                return $result;
            }
            foreach ($floor_images as $key2 => $floor_image) {
                if($key === $key2) {
                    FloorController::uploadFloorImage($user_id, $project_id, $model->id, $floor_image);
                }
            }
        }
        foreach ($project->galleries as $key => $gallery) {
            $model = GalleryController::createGallery($project_id, $gallery->name);
            GalleryController::uploadPhotos($user_id, $project_id, $model->id, $gallery_images[$key]);
        }

        if(isset($_FILES['logo']) && $_FILES['logo'] !== '') {
           $image = ImageController::uploadImage($user_id, $project_id, 'logo',$_FILES['logo']);
            if( $image !== false &&  isset($image->id) ){
                $project_model->project_logo = $image->id;
                $project_model->save();
            }
        }
        $project_link = md5($project_model->id . $project_model->name) . $project_model->id;
        $project_model->special_link = $project_link;
        $project_model->published = 1;
        $project_model->save();
        $result->ok = 1;
        $result->project_link = $project_link;
        return json_encode($result);
    }

}
