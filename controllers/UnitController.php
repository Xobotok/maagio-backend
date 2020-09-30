<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
use app\models\Units;
use app\models\Users;
use MailSender;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\String_;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class UnitController extends BaseController
{
    public $enableCsrfValidation = false;
    public static function updateUnit($project_id, $unit, $floor_id = null) {
        $floor = Units::findOne($unit->id);
        if($floor_id != null) {
            $floor->floor_id = (int)$floor_id;
        } else {
            $floor = new Units();
        }
        $floor->project_id = (int)$project_id;
        $floor->unit_number = (int)$unit->unit_number;
        $floor->bad = (int)$unit->bad;
        $floor->bath = (int)$unit->bath;
        $floor->price = (int)$unit->price;
        $floor->status = (int)$unit->status;
        if($unit->HOA != '') {
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
        return $floor;
    }
    public function actionDeleteUnit(){
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $unitModel = Units::findOne($data->unit_id);
        if($unitModel == null) {
            $result->ok = 0;
            $result->data = 'Unit not found';
            return $result;
        }
        if($unitModel->image_id != null) {
            ImageController::removeImage($unitModel->image_id);
        }
        $unitModel->delete();
        $result->ok = 1;
        $result->data = 'Unit '.$data->unit_id . ' deleted';
        return json_encode($result);
    }
    public function actionUpdateUnit(){
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $unit = json_decode($data->unit);
        $unitModel = Units::findOne($unit->id);
        if($unitModel == null) {
            $result->ok = 0;
            $result->data = 'Unit not found';
            return $result;
        }
        if($data->unit->image_id == null && $unitModel->image_id != null) {
            ImageController::removeImage($unitModel->image_id);
            $unitModel->image_id = null;
        }
        $unitModel->unit_number = (int)$unit->unit_number;
        $unitModel->floor_id = (int)$unit->floor;
        $unitModel->project_id = (int)$data->project_id;
        $unitModel->bad = (int)$unit->bad;
        $unitModel->bath = (int)$unit->bath;
        $unitModel->price = (int)$unit->price;
        $unitModel->status = (int)$unit->status;
        $unitModel->hoa = (int)$unit->HOA;
        $unitModel->int_sq = $unit->int_sq;
        $unitModel->ext_sq = $unit->ext_sq;
        $unitModel->bmr = (int)$unit->bmr;
        $unitModel->parking = (int)$unit->parking;
        $unitModel->mark_x = (string)$unit->mark_x;
        $unitModel->mark_y = (string)$unit->mark_y;
        $unitModel->save();
        if(isset($_FILES['image'])) {
            $img = ImageController::uploadImage($data->user_id, $data->project_id, 'unit', $_FILES['image'], $unit->floor, '', $unitModel->id);
            $unitModel->image_id = $img->id;
            $unitModel->save();
            $result->new_image = $img->image_link;
        }

        $result->ok = 1;
        $result->unit = $unitModel->attributes;

        return json_encode($result);
    }
    public function actionCreateNewUnit(){
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $unit = json_decode($data->unit);
        $unitModel = new Units();
        $unitModel->unit_number = (int)$unit->unit_number;
        $unitModel->floor_id = (int)$unit->floor;
        $unitModel->project_id = (int)$data->project_id;
        $unitModel->bad = (int)$unit->bad;
        $unitModel->bath = (int)$unit->bath;
        $unitModel->price = (int)$unit->price;
        $unitModel->status = (int)$unit->status;
        $unitModel->hoa = (int)$unit->HOA;
        $unitModel->int_sq = $unit->int_sq;
        $unitModel->ext_sq = $unit->ext_sq;
        $unitModel->bmr = (int)$unit->bmr;
        $unitModel->parking = (int)$unit->parking;
        $unitModel->mark_x = (string)$unit->mark_x;
        $unitModel->mark_y = (string)$unit->mark_y;
        $unitModel->save();
        if(isset($_FILES['image'])) {
            $img = ImageController::uploadImage($data->user_id, $data->project_id, 'unit', $_FILES['image'], $unit->floor, '', $unitModel->id);
            $unitModel->image_id = $img->id;
            $unitModel->save();
            $result->new_image = $img->image_link;
        }
        $result->ok = 1;
        $result->unit = $unitModel->attributes;
        return json_encode($result);
    }
    public static function createUnit($project_id, $unit, $floor_id = null) {
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
            return $floor;
    }
    public function actionGetBuildings() {

    }
    public static function getUnfloorUnits($project_id) {
        return Units::find()->where(['project_id' => $project_id, 'floor_id'=>null])->asArray()->all();
    }
    public static function updateUnits($user_id, $project_id, $actualUnits, $files){
        $removeUnits = [];
        $oldUnits = Units::find()->where(['project_id' => $project_id])->asArray()->all();
        foreach ($actualUnits as $key => $actualUnit) {

            if($actualUnit[0]->id == 0) {

               $unit = UnitController::createUnit($project_id, $actualUnit[0], $key);
            }
        }
        foreach ($oldUnits as $unit) {
            $flag = false;
            foreach ($actualUnits as $actualUnit) {
                if($actualUnit[0]->id == $unit['id']) {
                    $flag = true;
                }
            }
            if($flag != true) {
                $unit = Units::findOne($unit['id']);
                if($unit != null) {
                    $unit->delete();
                }
                }
            }
        return $actualUnits;
    }
    public static function getUnits($floor_id) {
        $unitModel = new Units();
        $units = $unitModel->find()->where(['floor_id' => $floor_id])->asArray()->all();
        foreach ($units as $key => $unit) {
            if(isset($unit['image_id']) && $unit['image_id'] != NULL) {
                $imageModel = new Images();
                $image = $imageModel->find()->where(['id' => $unit['image_id']])->asArray()->one();
                if(isset($image['image_link']) && $image['image_link'] != NULL) {
                    $units[$key]['image'] = $image['image_link'];
                }
            }
        }
        return $units;
    }
}
