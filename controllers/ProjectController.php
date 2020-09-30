<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Galleries;
use app\models\GalleryPhotos;
use app\models\Images;
use app\models\Maps;
use app\models\Projects;
use app\models\Units;
use app\models\Users;
use Faker\Provider\Image;
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

    public static function createProject($user_id, $project)
    {
        $model = new Projects();
        $model->user_id = $user_id;
        $model->name = $project->name;
        $model->save();
        return $model;
    }

    public function actionGetUserProjects()
    {
        $data = (object)yii::$app->request->get();
        $result = (object)[];
        $model = new Users();
        $user = $model->find()->where(['uid' => $data->user_id, 'login_token' => $data->token])->one();
        if ($user !== NULL) {
            $published_projects = Projects::find()->where(['user_id' => $data->user_id, 'published' => 1])->asArray()->all();
            foreach ($published_projects as $key => $project) {
                $model = new Images();
                $model = $model->find()->where(['id' => $project['project_logo']])->asArray()->one();
                if ($model !== NULL) {
                    $published_projects[$key]['project_logo'] = $model['image_link'];
                }
            }
            $draft_projects = Projects::find()->where(['user_id' => $data->user_id, 'published' => 0])->asArray()->all();
            foreach ($draft_projects as $key => $project) {
                $model = new Images();
                $model = $model->find()->where(['id' => $project['project_logo']])->asArray()->one();
                if ($model !== NULL) {
                    $draft_projects[$key]['project_logo'] = $model['image_link'];
                }

            }
            $result->ok = 1;
            $result->published_projects = $published_projects;
            $result->draft_projects = $draft_projects;
        } else {
            $result->ok = 0;
            $result->error = 'User not found';
        }
        return json_encode($result);
    }

    public function actionUpdateProject()
    {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        $project = json_decode($data->project);
        if ($authorisation->ok === 0) {
            $result->ok = 0;
            $result->message = 'Authorisation failed';
            return json_encode($result);
        }
        $images = $this->splitImages($_FILES);
        $projectModel = Projects::findOne($project->id);
        if(isset($project->project_logo) && $projectModel->project_logo != $project->project_logo){
            $logo = Images::findOne($projectModel->project_logo);
            if(isset($logo->attributes['id'])) {
                ImageController::removeImage($logo->attributes['id']);
            }
            if(isset($_FILES['logo'])) {
                $image = ImageController::uploadImage($data->user_id, $project->id, 'logo', $_FILES['logo']);
                $projectModel->project_logo = $image->attributes['id'];
            }
        }
        $actualFloors = $project->floors;
        $floor_result = FloorController::updateFloors($data->user_id, $project->id, $actualFloors, $images);
        $actualUnits = [];
        $extra_units = json_decode($data->extra_units);
        foreach ($project->floors as $floor) {
            foreach ($floor->units as $unit) {
                $actualUnits['units'][] = $unit;
            }
        }
        foreach ($extra_units as $unit) {
            $actualUnits['extra_units'][] = $unit;
        }

        $projectObj = $projectModel->find()->where(['id' => $project->id])->one();
        $projectModel->name = $project->name;
        $projectModel->save();
        return var_dump($floor_result);
    }
    public function  actionUpdateOverview() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        if($data->project_id && $data->project_id) {
            $project = Projects::findOne($data->project_id);
        } else {
            $project = new Projects();
        }
        $project->name = $data->name;
        $project->user_id = $data->user_id;
        if(isset($_FILES['logo'])) {
            if($project->project_logo) {
                $old_logo = $project->project_logo;
                $project->project_logo = null;
                $project->save();
                ImageController::removeImage($old_logo);
            }
            $img = ImageController::uploadImage($data->user_id, $data->project_id, 'logo', $_FILES['logo'], '', '', '');
            $project->project_logo = $img->id;
            $project->save();

        }
        $project->save();
        $result->ok = 1;
        $result->project = $project->attributes;
        return json_encode($result);
    }
    public function actionUpdateFloorPlates() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        if($data->project_id) {
            $project = Projects::findOne($data->project_id);
        } else {
            $result->ok = 0;
            $result->message = 'Project not found';
        }
        $images = [];
        foreach ($_FILES as $key => $file) {
            $images[explode('floor_images_', $key)[1]] = $file;
        }
        $floors = json_decode($data->floors);
        foreach ($floors as $key => $floor) {
            if(isset($floor->id)) {
                $floorModel = Floors::findOne($floor->id);
            } else {
                $floorModel = new Floors();
                $floorModel->project_id = $data->project_id;
                $floorModel->save();
            }
            $floorModel->number = $floor->number;
            if(isset($images[$key])) {
                if($floorModel->image_id != null) {
                    ImageController::removeImage($floorModel->image_id);
                }
                $img = ImageController::uploadImage($data->user_id, $data->project_id, 'floor', $images[$key], $floorModel->id, '', '');
                $floorModel->image_id = $img->id;
            }
            $floorModel->save();
        }

    }
    public function actionSaveNewProject()
    {
        $result = (object)[];
        $floor_images = [];
        $gallery_images = [];
        $unit_images = [];
        $data = (object)yii::$app->request->post();

        $user_id = (isset($data->user_id)) ? $data->user_id : 0;
        $token = (isset($data->token)) ? $data->token : 0;
        $authorisation = $this->checkAuthorisation($user_id, $token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $project = json_decode($data->project);
        $project_model = self::createProject($user_id, $project);

        if (count($_FILES) > 0) {
            $files = $_FILES;
            foreach ($files as $key => $file) {
                $findme = 'floor';
                $pos = strpos($key, $findme);
                if ($pos !== false) {
                    $findme = 'unit';
                    $pos = strpos($key, $findme);
                    if ($pos === false) {
                        $floor_id = explode('floor-', $key)[1];
                        $floor_images[$floor_id] = $file;
                    } else {
                        $floor_id = explode('-unit', explode('floor-', $key)[1])[0];
                        $unit_images[$floor_id][explode('unit-', $key)[1]] = $file;
                    }
                }
                $findme = 'gallery';
                $pos = strpos($key, $findme);
                if ($pos !== false) {
                    $gallery_number = explode('_', explode('gallery-', $key)[1])[0];
                    $photo_number = explode('_', explode('gallery-', $key)[1])[1];
                    $gallery_images[$gallery_number][$photo_number] = $file;
                }
            }
        }
        if (!isset($project_model->id)) {
            $result->ok = 0;
            $result->error = 'The project cannot be saved';
            return $result;
        }
        $project_id = $project_model->id;
        if ($project->map->lat != '' && $project->map->lng != '') {
            $model = new Maps();
            $model->project_id = $project_id;
            $model->lat = '' . $project->map->lat;
            $model->lng = '' . $project->map->lng;
            $model->save();
        }
        foreach ($unit_images as $key => $images) {
            foreach ($images as $key2 => $image) {
                if (isset($project->floors[$key]->units[$key2])) {
                    $project->floors[$key]->units[$key2]->image = $image;
                }
            }
        }

        foreach ($project->floors as $key => $floor) {
            $model = FloorController::createFloor($user_id, $project_id, $floor, $key);

            if (!isset($model->id)) {
                $result->ok = 0;
                $result->error = 'The floors cannot be saved';
                return $result;
            }
            foreach ($floor_images as $key2 => $floor_image) {
                if ($key === $key2) {
                    FloorController::uploadFloorImage($user_id, $project_id, $model->id, $floor_image);
                }
            }
        }
        foreach ($project->galleries as $key => $gallery) {
            $model = GalleryController::createGallery($project_id, $gallery->name);
            GalleryController::uploadPhotos($user_id, $project_id, $model->id, $gallery_images[$key]);
        }

        if (isset($_FILES['logo']) && $_FILES['logo'] !== '') {
            $image = ImageController::uploadImage($user_id, $project_id, 'logo', $_FILES['logo']);
            if ($image !== false && isset($image->id)) {
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

    private function splitImages($files)
    {
        $result = [];
        $result['floor_images'] =[];
        $result['gallery_images'] = [];
        $result['unit_images'] = [];
        $result['avatar_image'] = [];
        foreach ($files as $key => $file) {
            $findme = 'floor';
            $pos = strpos($key, $findme);
            if ($pos !== false) {
                $findme = 'unit';
                $pos = strpos($key, $findme);
                if ($pos === false) {
                    $floor_id = explode('floor-', $key)[1];
                    $result['floor_images'][$floor_id] = $file;
                } else {
                    $floor_id = explode('-unit', explode('floor-', $key)[1])[0];
                    $result['unit_images'][explode('unit-', $key)[1]] = $file;
                }
            }
            $findme = 'gallery';
            $pos = strpos($key, $findme);
            if ($pos !== false) {
                $gallery_number = explode('_', explode('gallery-', $key)[1])[0];
                $photo_number = explode('_', explode('gallery-', $key)[1])[1];
                $result['gallery_images'][$gallery_number][$photo_number] = $file;
            }
        }
        return $result;
    }

    public function actionShow()
    {
        $data = (object)yii::$app->request->get();
        $result = (object)[];
        $model = new Projects();
        $project = $model->find()->where(['special_link' => $data->project, 'published' => 1])->asArray()->one();
        if ($project == NULL) {
            $result->ok = 0;
            $result->error = 'Project not found.';
        }
        $model = new Images();
        if ($project['project_logo'] != NULL) {
            $image = $model->find()->where(['id' => $project['project_logo']])->asArray()->one();
            $project['project_logo'] = $image['image_link'];
        }
        $model = new Floors();
        $floors = $model->find()->where(['project_id' => $project['id']])->orderBy('number')->asArray()->all();
        foreach ($floors as $key => $floor) {
            $floors[$key]['image'] = Images::find()->where(['id' => $floor['image_id']])->asArray()->one();
            $unit_model = new Units();
            $units = $unit_model->find()->where(['floor_id' => $floor['id']])->asArray()->all();
            foreach ($units as $key2 => $unit) {
                $unit_image_model = new Images();
                $image = $unit_image_model->find()->where(['id' => $unit['image_id']])->one();
                if ($image != NULL) {
                    $units[$key2]['image'] = $image->image_link;
                }
            }
            $floors[$key]['units'] = $units;
        }
        $project['floors'] = $floors;
        $gallery = new Galleries();
        $gallery_array = $gallery->find()->where(['project_id' => $project['id']])->all();
        $result_galleries = [];
        foreach ($gallery_array as $key => $gallery) {
            $image_photos = $gallery->getGalleryPhotos()->all();
            $result_galleries[$key]['name'] = $gallery->name;
            foreach ($image_photos as $photo) {
                $image = $photo->getImage()->one();
                $result_galleries[$key]['photos'][] = $image->image_link;
            }
        }
        $project['galleries'] = $result_galleries;
        $model = new Maps();
        $map = $model->find()->where(['project_id' => $project['id']])->asArray()->one() ?: '';
        if (isset($map['lng'])) {
            $map['lng'] = (double)$map['lng'];
        }
        if (isset($map['lat'])) {
            $map['lat'] = (double)$map['lat'];
        }
        $project['map'] = $map;
        $result->ok = 1;
        $result->data = $project;
        return json_encode($result);
    }

    public function actionDelete()
    {
        $data = (object)yii::$app->request->post();
        $result = (object)[];
        if ($this->checkAuthorisation($data->user_id, $data->token)->ok === 0) {
            $result->ok = 0;
            $result->message = 'Authorisation failed';
            return json_encode($result);
        }
        $projectModel = new Projects();
        $project = $projectModel->find()->where(['user_id' => $data->user_id, 'id' => $data->project_id])->one();
        if ($project != NULL) {
            if($project->attributes['project_logo'] != null) {
                ImageController::removeImage($project->attributes['project_logo']);
            }
            $floors = Floors::find()->where(['project_id' => $project->attributes['id']])->asArray()->all();
            foreach ($floors as $floor) {
                if($floor['image_id'] != null) {
                    ImageController::removeImage($floor['image_id']);
                }
            }
            $units = Units::find()->where(['project_id' => $project->attributes['id']])->asArray()->all();
            foreach ($units as $unit) {
                if($unit['image_id'] != null) {
                    ImageController::removeImage($unit['image_id']);
                }
            }
            $galleries = Galleries::find()->where(['project_id' => $project->attributes['id']])->asArray()->all();
            foreach ($galleries as $gallery) {
                $galleryPhotos = GalleryPhotos::find()->where(['gallery_id' => $gallery['id']])->asArray()->all();
                foreach ($galleryPhotos as $galleryPhoto) {
                    ImageController::removeImage($galleryPhoto['image_id']);
                }
            }
            $project->delete();
            if (count($project->oldAttributes) == 0) {
                $result->ok = 1;
                $result->message = 'Project removed.';
                $result->project_id = $data->project_id;
            } else {
                $result->ok = 0;
                $result->message = 'Project not removed.';
            }
        } else {
            $result->ok = 0;
            $result->message = 'Project not found.';
        }
        return json_encode($result);
    }

    public function actionTake()
    {
        $data = (object)yii::$app->request->get();
        $result = (object)[];
        if ($this->checkAuthorisation($data->user_id, $data->token)->ok === 0) {
            $result->ok = 0;
            $result->message = 'Authorisation failed';
            return json_encode($result);
        }
        $project = ProjectController::getProjectById((int)$data->project_id);
        $result->ok = 1;
        $result->data = $project;
        return json_encode($result);
    }

    public static function getProjectById($id)
    {
        $projectModel = new Projects();
        $result = (object)[];
        $project = $projectModel->find()->where(['id' => $id])->asArray()->one();
        if ($project == NULL) {
            $result->ok = 0;
            $result->message = 'Project not found';
            return $result;
        }
        if (isset($project['project_logo']) && $project['project_logo'] != NULL) {
            $imageModel = new Images();
            $logo = $imageModel->find()->where(['id' => $project['project_logo']])->asArray()->one()['image_link'];
            $project['logo'] = $logo;
        }
        $project['floors'] = FloorController::getFloor($project['id']);
        $project['galleries'] = GalleryController::getGalleries($project['id']);
        $unfloorUnits = UnitController::getUnfloorUnits($project['id']);
        if($unfloorUnits != null) {
            $project['unfloor_units'] = $unfloorUnits;
        } else {
            $project['unfloor_units'] =[];
        }

        $mapModel = new Maps();
        $map = $mapModel->find()->where(['project_id' => $id])->asArray()->one();
        if ($map != NULL) {
            $project['map']['lat'] = (double)$map['lat'];
            $project['map']['lng'] = (double)$map['lng'];
            $project['map']['id'] = $map['id'];
        } else {
            $project['map'] = '';
        }
        return $project;
    }
    public function actionPublish() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $project =Projects::findOne($data->project_id);
        if($project->published == 1) {
            $project->published = 0;
        } else {
            $project->published = 1;
        }
        if( $project->special_link == null) {
            $project_link = md5($project->id . $project->name) . $project->id;
            $project->special_link = $project_link;
        }
        $project->save();
        $result->ok = 1;
        $result->published = $project->published;
        $result->personal_link = $project->special_link;
        return json_encode($result);
    }
}
