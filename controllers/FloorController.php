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
    public static function createFloor($user_id, $project_id, $floor, $number = 0) {
        $model = new Floors();
        $model->project_id = $project_id;
        $model->number = $number + 1;
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
    public static function updateFloorUnits($user_id, $project_id, $floor_id = null, $newUnits, $images){
        $oldUnits = Units::find()->where(['floor_id' => $floor_id])->all();
        foreach ($oldUnits as $unit) {
            $flag = false;
            foreach ($newUnits as $newUnit) {
                if($newUnit->id == $unit['id']) {
                    $flag = true;
                }
            }
            if($flag == false) {
                $removedUnit = Units::findOne($unit['id']);
                if($removedUnit->image_id != null) {
                    ImageController::removeImage($removedUnit->image_id);
                }
               $removedUnit->delete();
            }
        }
        foreach ($newUnits as $unit) {
            $unitModel = Units::findOne($unit->id);
            if($unitModel == null) {

                $floor = new Units();
                if($floor_id != null) {
                    $floor->floor_id = (int)$floor_id;
                } else {
                    $floor->floor_id = null;
                }
                $floor->project_id = (int)$project_id;
                $floor->unit_number = (int)$unit->unit_number;
                $floor->bad = (int)$unit->bad;
                $floor->bath = (int)$unit->bath;
                $floor->price = (int)$unit->price;
                $floor->status = (int)$unit->status;
                if($unit->hoa != '') {
                    $floor->hoa = (int)$unit->HOA;
                }
                $floor->int_sq = (int)$unit->int_sq;
                if($unit->ext_sq != '') {
                    $floor->ext_sq = (int)$unit->ext_sq;
                }
                $floor->bmr = (int)$unit->bmr;
                $floor->parking = (int)$unit->parking;
                if($unit->mark_x != '') {
                    $floor->mark_x = '' . $unit->mark_x;
                }
                if($unit->mark_y != '') {
                    $floor->mark_y = '' . $unit->mark_y;
                }

                $floor->save();
                return var_dump($floor);
                if(isset($unit->unitImageId) && $unit->unitImageId != '') {
                    foreach ($images['unit_images'] as $img) {
                        if(($img['name'] .'_'. $img['size']) == $unit->unitImageId) {
                            $uploaded_image = ImageController::uploadImage($user_id, $project_id, 'unit', $img, $floor_id, '', $unit->id);
                            $unitModel->image_id = $uploaded_image->id;
                            $unitModel->save();
                        }
                    }
                }
            } else {
                $unitModel = UnitController::updateUnit($project_id, $unit, $floor_id);
                if(isset($unit->unitImageId) && $unit->unitImageId != '') {
                    foreach ($images['unit_images'] as $img) {
                        if(($img['name'] .'_'. $img['size']) == $unit->unitImageId) {
                            $uploaded_image = ImageController::uploadImage($user_id, $project_id, 'unit', $img, $floor_id, '', $unit->id);
                            $unitModel->image_id = $uploaded_image->id;
                            $unitModel->save();
                        }
                    }
                }
            }
        }
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
