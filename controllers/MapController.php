<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
use app\models\MapMarkers;
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

class MapController extends BaseController
{
    public $enableCsrfValidation = false;

    public function actionSearchAddress() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $map = Maps::find()->where(['project_id' => $data->project_id])->asArray()->one();
        if(isset($map['id'])) {
            $map = Maps::findOne($map['id']);
        } else {
            $map = new Maps();
        }
        $new_map =  'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($data->address) . '&key=AIzaSyDEzKHEUbk3ocLvIgBGMOsJjguHEj0LR4s';
        $new_map = file_get_contents($new_map);
        $new_map = json_decode($new_map);
        $new_lat ='';
        $new_lng = '';
        $new_address = '';
        if(isset($new_map->results[0]->geometry->location->lat)) {
            $new_lat = (string)$new_map->results[0]->geometry->location->lat;
        }
        if(isset($new_map->results[0]->geometry->location->lng)) {
            $new_lng = (string)$new_map->results[0]->geometry->location->lng;
        }
        if(isset($new_map->results[0]->formatted_address)) {
            $new_address = $new_map->results[0]->formatted_address;
        }
        if($new_lat != '' && $map->lat != $new_lat) {
            $map->lat = $new_lat;
            $map->lng = $new_lng;
            $map->address = $new_address;
            $map->save();
            $result->ok = 1;
            $result->map = $map->attributes;
            MarkerController::CreateMarkers($map->lat, $map->lng, $data->project_id);
            return json_encode($result);
        }
        $result->ok = 0;
        $result->map = 'The same address';
        return json_encode($result);
    }
    public function actionSearchPlace() {
        $data = (object)yii::$app->request->post();
        $query = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $data->input . '&key=AIzaSyDEzKHEUbk3ocLvIgBGMOsJjguHEj0LR4s';
        $result = file_get_contents($query);
        $result = json_decode($result);
        $data = (object)[];
        if(isset($result->status) && $result->status == 'OK') {
            $data->result = $result->results;
            $data->ok = 1;
        } else {
            $data->ok = 0;
            $data->message = 'Nothing found';
        }
        return json_encode($data);
    }
    public function actionSearchNearPlaces() {
        $query = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=-33.8670522,151.1957362&radius=500&type=food&key=AIzaSyDEzKHEUbk3ocLvIgBGMOsJjguHEj0LR4s';
        $result = file_get_contents($query);
        $result = json_decode($result);
        $data = (object)[];
        if(isset($result->status) && $result->status == 'OK') {
            $data->result = $result->results;
            $data->ok = 1;
        } else {
            $data->ok = 0;
            $data->message = 'Nothing found';
        }
        return json_encode($data);
    }
}
