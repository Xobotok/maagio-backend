<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
use app\models\LotInfo;
use app\models\UnitMark;
use app\models\UnitPhotos;
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
        if($data->house_type == 2) {
            $unitModel = LotInfo::findOne((int)$data->unit_id);
        } else {
            $unitModel = Units::findOne((int)$data->unit_id);
        }
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
        $result->house_type = $data->house_type;
        return json_encode($result);
    }
    public function actionUpdatePhotos() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $photos = json_decode($data->photos);
        $unit_images = UnitPhotos::find()->where(['unit_id' => $data->unit_id])->asArray()->all();
        foreach ($unit_images as $image) {
            $flag = false;
            foreach ($photos as $photo) {
                if($photo->id == $image['id']){
                    $flag = true;
                }
            }
            if(!$flag) {
                ImageController::removeImage($image['image_id']);
            }
        }
        foreach ($_FILES as $photo) {
            $img = ImageController::uploadImage($data->user_id, $data->project_id, 'unit-photo', $photo, '', '', $data->unit_id);
            $unit_photo = new UnitPhotos();
            $unit_photo->unit_id = (int)$data->unit_id;
            $unit_photo->image_id = (int)$img->id;
            $unit_photo->image_link = $img->image_link;
            $unit_photo->save();
        }
        $result->unit_photos = UnitPhotos::find()->where(['unit_id' => $data->unit_id])->asArray()->all();
        $result->ok = 1;
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
        $data->unit = json_decode($data->unit);
        if($data->house_type == 1) {
            if($unit->id != '') {
                $unitModel = Units::findOne($unit->id);
                if($unitModel == null) {
                    $result->ok = 0;
                    $result->data = 'Unit not found';
                    return $result;
                }

                if($unit->image_id == null && $unitModel->image_id != null) {
                    ImageController::removeImage($unitModel->image_id);
                    $unitModel->image_id = null;
                }
            } else {
                $unitModel = new Units();
            }
            if((int)$unitModel->floor_id != (int)$unit->floor) {
                $unitModel->floor_id = (int)$unit->floor;
                $newFloor = Floors::findOne((int)$unit->floor)->number;
            }
            if($unit->mark == true) {
                $unitModel->mark = 1;
            } else {
                $unitModel->mark = 0;
                $mark = UnitMark::find()->where(['unit_id' => $unitModel->id])->one();
                if($mark != NULL) {
                    $mark->delete();
                }
            }
            $unitModel->project_id = $data->project_id;
            $unitModel->unit_number = $unit->unit_number;
            $unitModel->bad = (int)$unit->bad;
            $unitModel->bath = (int)$unit->bath;
            $unitModel->price = (int)$unit->price;
            $unitModel->status = (int)$unit->status;
            $unitModel->hoa = (int)$unit->HOA;
            $unitModel->int_sq = $unit->int_sq;
            $unitModel->ext_sq = $unit->ext_sq;
            $unitModel->bmr = (int)$unit->bmr;
            $unitModel->parking = (int)$unit->parking;
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
                $markModel->unit_id = (int)$unitModel->id;
                $markModel->save();
            }
        } else {
            if($unit->id != '') {
                $unitModel = LotInfo::findOne($unit->id);
                if($unitModel == null) {
                    $result->ok = 0;
                    $result->data = 'Unit not found';
                    return $result;
                }
                if($unit->image_id == null && $unitModel->image_id != null) {
                    ImageController::removeImage($unitModel->image_id);
                    $unitModel->image_id = null;
                }
            } else {
                $unitModel = LotInfo::find()->where(['project_id' => $data->project_id])->one();
                if($unitModel == null) {
                    $unitModel = new LotInfo();
                } else {
                    $unitModel = LotInfo::findOne($unitModel->attributes['id']);
                }
            }
        }
        $unitModel->unit_number = $unit->unit_number;
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
        $unitModel->save();
        if(isset($_FILES['image'])) {
            if($data->house_type == 2) {
                $unit->floor = 'lot_info';
                $unitModel = LotInfo::find()->where(['project_id' => $data->project_id])->one();
                if($unitModel == null) {
                    $unitModel = new LotInfo();
                } else {
                    $unitModel = LotInfo::findOne($unitModel->attributes['id']);
                }
            }
            $img = ImageController::uploadImage($data->user_id, $data->project_id, 'unit', $_FILES['image'], $unit->floor, '', $unitModel->id);
            $unitModel->image_id = (int)$img->id;
            $unitModel->save();
            $result->new_image = $img->image_link;
        }
        $result->ok = 1;
        $result->unit = $unitModel->attributes;
        if($unitModel->image_id != null) {
            $result->unit['image'] = Images::findOne($unitModel->image_id)->image_link;
        }
        if(isset($markModel) && count($markModel->errors) == 0) {
            $result->unit['unit_mark'] = $markModel->attributes;
        } else if(isset($markModel) && count($markModel->errors) > 0) {
            $result->unit['unit_mark'] = $markModel->oldAttributes;
        }
        if(isset($newFloor)) {
            $result->unit['newFloor'] = $newFloor;
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
            $unit_photos_obj = UnitPhotos::find()->where(['unit_id' => $unit['id']])->asArray()->all();
            $unit_photos = [];
            foreach ($unit_photos_obj as $key2 => $photo) {
                $image = Images::findOne($photo['image_id'])->image_link;
                $unit_photos[$key2]['image_link'] = $image;
                $unit_photos[$key2]['id'] = $photo['id'];
                $unit_photos[$key2]['image_id'] = $photo['image_id'];
            }
            $units[$key]['photos'] = $unit_photos;
            if($unit['mark'] != 0) {
                $unit_mark = UnitMark::find()->where(['unit_id' => $unit['id']])->one();
                $units[$key]['unit_mark'] = $unit_mark->attributes;
            } else {
                $units[$key]['unit_mark'] = null;
            }
        }
        return $units;
    }
}
