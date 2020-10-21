<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
use app\models\UnitMark;
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
        if($unit->id != '') {
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
        } else {
            $unitModel = new Units();
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
        if($unit->mark == true) {
            $unitModel->mark = 1;
        } else {
            $unitModel->mark = 0;
        }
        $unitModel->save();
        if($unit->mark == true) {
            $unit_id = $unitModel->id;
            $markModel = UnitMark::find()->where(['unit_id' => $unit_id])->one();
            if($markModel != null) {
                $markModel = UnitMark::findOne($markModel->attributes['id']);
            } else {
                $markModel = new UnitMark();
            }
            $markModel->x = (string)$unit->unit_mark->x_prc;
            $markModel->y = (string)$unit->unit_mark->y_prc;
            $markModel->font_size = (string)$unit->unit_mark->font_size;
            $markModel->width = (string)$unit->unit_mark->width_prc;
            $markModel->height = (string)$unit->unit_mark->height_prc;
            $markModel->unit_id = $unitModel->id;
            $markModel->save();
        }
        if(isset($_FILES['image'])) {
            $img = ImageController::uploadImage($data->user_id, $data->project_id, 'unit', $_FILES['image'], $unit->floor, '', $unitModel->id);
            $unitModel->image_id = $img->id;
            $unitModel->save();
            $result->new_image = $img->image_link;
        }
        $result->ok = 1;
        $result->unit = $unitModel->attributes;
        if(isset($markModel) && count($markModel->errors) == 0) {
            $result->unit['unit_mark'] = $markModel->attributes;
        } else if(isset($markModel) && count($markModel->errors) > 0) {
            $result->unit['unit_mark'] = $markModel->oldAttributes;
        }
        return json_encode($result);
    }
    public static function getUnfloorUnits($project_id) {
        return Units::find()->where(['project_id' => $project_id, 'floor_id'=>null])->asArray()->all();
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
            if($unit['mark'] != 0) {
                $unit_mark = UnitMark::find()->where(['unit_id' => $unit['id']])->one();
                $units[$key]['unit_mark'] = $unit_mark->attributes;
            } else {
                $units[$key]['unit_mark'] = (object)['id'=> 0, 'width' => 150, 'height' => 100, 'x' => 0, 'y' => 0, 'font_size' => 16];
            }
        }
        return $units;
    }
}
