<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
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

class FloorController extends BaseController
{
    public $enableCsrfValidation = false;
    public function actionCreateNewFloor() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $floor = new Floors();
        $floor->project_id = $data->project_id;
        $floor->number = $data->floor_number;
        $floor->save();
        $result->ok = 1;
        $result->floor = $floor->attributes;
        return json_encode($result);
    }
    public function actionUpdateNumber() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $floor_1 = json_decode($data->floor_1);
        $floor_2 = json_decode($data->floor_2);
        $floorModel = Floors::findOne($floor_1->id);
        $floorModel->number = $floor_1->number;
        $floorModel->save();
        $floor_1 = $floorModel->attributes;
        $floorModel2 = Floors::findOne($floor_2->id);
        $floorModel2->number = $floor_2->number;
        $floorModel2->save();
        $floor_2 = $floorModel2->attributes;
        $result->ok = 1;
        $result->floor_1 = $floor_1;
        $result->floor_2 = $floor_2;
        return json_encode($result);
    }
    public function actionUploadPhoto() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $floor = Floors::findOne($data->floor_id);
        if($floor->image_id != null) {
            $old_image = $floor->image_id;
            $floor->image_id = null;
            $floor->save();
            ImageController::removeImage($old_image);
        }
        if(isset($_FILES['image'])) {
            $img = ImageController::uploadImage($data->user_id, $data->project_id, 'floor', $_FILES['image'], $data->floor_id, '', '');
            $floor->image_id = $img->id;
        }
        $floor->save();
        $result->ok = 1;
        $result->floor = $floor->attributes;
        return json_encode($result);
    }
    public function actionRemoveFloor() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $floor = Floors::findOne($data->floor_id);
        $project_id = $floor->project_id;
        if($floor->image_id != null) {
            ImageController::removeImage($floor->image_id);
        }
        $floor->delete();
        $floors = Floors::find()->where(['project_id' => $project_id])->orderBy('number')->asArray()->all();
        foreach ($floors as $key => $floor) {
            $item = Floors::findOne($floor['id']);
            $item->number = $key + 1;
            $item->save();
        }
        $result->ok = 1;
        return json_encode($result);
    }
    public static function updateFloors($user_id, $project_id, $newFloors, $images){
        $floorsModel = new Floors();
        $oldFloors = $floorsModel->find()->where(['project_id' => $project_id])->asArray()->all();
        foreach ($newFloors as $key => $floor) {
            if (isset($floor->id)) {
                $floorObj = Floors::findOne($floor->id);
                $floorObj->id = $floor->id;
                $floorObj->project_id = $project_id;
                $floorObj->number = $key + 1;
                if (isset($images['floor_images'][$key])) {
                    if($floorObj->image_id != NULL) {
                        ImageController::removeImage($floorObj->image_id);
                    }
                    $floor_image = ImageController::uploadImage($user_id, $project_id, 'floor', $images['floor_images'][$key], $floor->id);
                    $floorObj->image_id = $floor_image->id;
                }
                $floorObj->save();
                self::updateFloorUnits($user_id, $project_id, $floorObj->id, $floor->units, $images);
                foreach ($oldFloors as $oldFloor) {
                    $flag = false;
                    foreach ($newFloors as $actualFloor) {
                        if($actualFloor->id == $oldFloor['id']) {
                            $flag = true;
                        }
                    }
                    if($flag === false) {
                        $floorController = Floors::findOne($oldFloor['id']);
                        if($floorController != null) {
                            if($floorController->attributes['image_id'] != NULL) {
                                ImageController::removeImage($floorController->attributes['image_id']);
                            }
                            $floorController->delete();
                        }
                    }
                }
            } else {
                $floorObj = new Floors();
                $floorObj->project_id = $project_id;
                $floorObj->number = $key + 1;
                $floorObj->save();
                if(isset($images[$key])) {
                    $img = ImageController::uploadImage($user_id, $project_id, 'floor', $images[$key], $floorObj->attributes['id'], '','');
                    $floorObj->image_id = $img->attributes['id'];
                    $floorObj->save();
                }
            }
        }
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
    public static function getFloor($project_id) {
        $floorsModel = new Floors();
        $floors = $floorsModel->find()->where(['project_id' => $project_id])->orderBy('number')->asArray()->all();
        foreach ($floors as $key => $floor) {
            $imageController = new Images();
            $image = $imageController->find()->where(['id' => $floor['image_id']])->asArray()->one();
            if(isset($image['image_link'])) {
                $floors[$key]['image'] = $image['image_link'];
                $floors[$key]['preview'] = $image['image_link'];
            } else {
                $floors[$key]['image'] = null;
            }
            $floors[$key]['units'] = UnitController::getUnits($floor['id']);
        }
        return $floors;
    }
}
