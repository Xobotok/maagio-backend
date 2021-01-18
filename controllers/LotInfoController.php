<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
use app\models\LotInfo;
use app\models\LotPhotos;
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

class LotInfoController extends BaseController
{
    public $enableCsrfValidation = false;
  public static function getLotInfo($project_id) {
      $model = new LotInfo();
      $model = $model->find()->where(['project_id'=>$project_id])->one();
      $photos = LotPhotos::find()->where(['lot_id' => $model->attributes['id']])->asArray()->all();
      $result = $model->attributes;
      $result['image'] = Images::findOne($model->attributes['image_id'])->image_link;
      $result['photos'] = $photos;
      return $result;
  }
    public function actionUpdatePhotos() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $photos = json_decode($data->photos);
        if($data->unit_id)
        $unit_images = LotPhotos::find()->where(['lot_id' => $data->unit_id])->asArray()->all();
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
            $unit_photo = new LotPhotos();
            $unit_photo->lot_id = (int)$data->unit_id;
            $unit_photo->image_id = (int)$img->id;
            $unit_photo->image_link = $img->image_link;
            $unit_photo->save();
        }
        $result->unit_photos = LotPhotos::find()->where(['lot_id' => $data->unit_id])->asArray()->all();
        $result->ok = 1;
        return json_encode($result);
    }
}
