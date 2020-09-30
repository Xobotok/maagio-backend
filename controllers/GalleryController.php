<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Galleries;
use app\models\GalleryPhotos;
use app\models\Images;
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

class GalleryController extends BaseController
{
    public $enableCsrfValidation = false;

    public static function createGallery($project_id, $name)
    {
        $model = new Galleries();
        $model->name = '' . $name;
        $model->project_id = (int)$project_id;
        $model->save();
        return $model;
    }
    public function actionRemoveGallery() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $gallery_id = json_decode($data->gallery_id);
        $galleryModel = Galleries::findOne($data->gallery_id);
        $galleryPhotos = GalleryPhotos::find()->where(['gallery_id' => $gallery_id])->asArray()->all();
        foreach ($galleryPhotos as $galleryPhoto) {
            $galleryPhotosModel = GalleryPhotos::findOne($galleryPhoto['id']);
            $image_id = $galleryPhotosModel->image_id;
            $galleryPhotosModel->delete();
            ImageController::removeImage($image_id);
        }
        $galleryModel->delete();
        $result->ok = 1;
        $result->data = 'Gallery ' .$gallery_id. ' deleted.';
        return json_encode($result);
    }
    public function actionUpdateGallery() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $gallery = json_decode($data->gallery);
        if($gallery->id != null) {
            $galleryModel = Galleries::findOne($gallery->id);
            if($galleryModel->name != $gallery->name) {
                $galleryModel->name = $gallery->name;
            }
            $galleryModel->save();
        } else {
            $galleryModel = new Galleries();
            $galleryModel->name = $gallery->name;
            $galleryModel->project_id = $data->project_id;
            $galleryModel->save();
        }
        $oldPhotos = GalleryPhotos::find()->where(['gallery_id' => $gallery->id])->asArray()->all();
        foreach ($oldPhotos as $key => $oldPhoto) {
            $flag = false;
            foreach ($gallery->photos as $photo) {
                if($photo->id == $oldPhoto['image_id']) {
                    $flag = true;
                }
            }
            if($flag === false) {
                $photoModel = GalleryPhotos::find()->where(['image_id' => $oldPhoto['image_id']])->one();
                $photoModel->delete();
                ImageController::removeImage($oldPhoto['image_id']);
            }
        }

        foreach ($_FILES as $key => $file) {
            $img = ImageController::uploadImage($data->user_id, $data->project_id, 'gallery_image', $file, '', $galleryModel->id, '');
            $photo = new GalleryPhotos();
            $photo->gallery_id = $galleryModel->id;
            $photo->image_id = $img->id;
            $photo->number = $key;
            $photo->save();
        }
        $result->ok = 1;
        $result->gallery = $galleryModel->attributes;
        return json_encode($result);
    }
    public static function uploadPhotos($user_id, $project_id, $gallery_id, $photos_array)
    {
        $result = (object)[];
        $result->failed_uploaded = [];
        $number = 0;
        foreach ($photos_array as $photo) {
            $image = ImageController::uploadImage($user_id, $project_id, 'gallery_image', $photo, '', $gallery_id);
            if (!$image->id) {
                $result->failed_uploaded[] = $image->name;
            } else {
                $model = new GalleryPhotos();
                $model->image_id = $image->id;
                $model->gallery_id = $gallery_id;
                $model->number = $number;
                $model->save();
            }
            $number++;
        }
        return $result;
    }

    public function actionGetBuildings()
    {

    }

    public static function getGalleries($project_id)
    {
        $galleryModel = new Galleries();
        $galleries = $galleryModel->find()->where(['project_id' => $project_id])->asArray()->all();
        foreach ($galleries as $key => $gallery) {
            $galleryPhotosModel = new GalleryPhotos();
            $galleryPhotos = $galleryPhotosModel->find()->where(['gallery_id' => $gallery['id']])->asArray()->all();
            foreach ($galleryPhotos as $photo) {
                $imageModel = new Images();
                $image = $imageModel->find()->where(['id' => $photo['image_id']])->asArray()->one();
                $galleries[$key]['photos'][] = $image;
                $galleries[$key]['previews'][] = $image;
            }
        }
        return $galleries;
    }
}
