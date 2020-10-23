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

class MarkerController extends BaseController
{
    public $enableCsrfValidation = false;

    public function actionAddMarker()
    {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
       $marker = json_decode($data->marker);
        $query = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$marker->lat.','.$marker->lng.'&key=AIzaSyDEzKHEUbk3ocLvIgBGMOsJjguHEj0LR4s';
        $query = file_get_contents($query);
        $query = json_decode($query);
        $address = $query->results[0]->formatted_address;
        $model = new MapMarkers();
        $model->lat = (string)$marker->lat;
        $model->lng = (string)$marker->lng;
        $model->project_id = $data->project_id;
        $model->type = (int)$marker->type;
        if(isset($marker->name) && $marker->name != null) {
            $model->name = (string)$marker->name;
        }
        if(isset($marker->description) && $marker->description != null) {
            $model->description = (string)$marker->description;
        }
        if($address != null) {
            $model->address = (string)$address;
        }
        $model->save();
        if($model->id != null) {
            $result->ok = 1;
            $result->marker = $model->attributes;
        } else {
            $result->ok = 0;
            $result->message = 'Something wrong';
            $result->marker = $model;
        }
        return json_encode($result);
    }
    public static function getMarkers($project_id) {
        $result['user_markers'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 0])->asArray()->all();
        $result['culture'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 1, 'type' => 1])->asArray()->all();
        $result['restaurant'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 1, 'type' => 2])->asArray()->all();
        $result['sport'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 1, 'type' => 3])->asArray()->all();
        $result['nature'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 1, 'type' => 4])->asArray()->all();
        return $result;
    }
    public function actionDeleteMarker() {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $marker = MapMarkers::findOne($data->marker_id);
        if($marker != null) {
            $result->marker = $marker;
            $marker->delete();
            $result->ok = 1;
           return json_encode($result);
        } else {
            $result->message = "Marker doesn't found";
            $result->ok = 0;
            return json_encode($result);
        }
    }
    private static function getNextPage($pagetoken) {
        $query = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?pagetoken='.$pagetoken.'&key=AIzaSyDEzKHEUbk3ocLvIgBGMOsJjguHEj0LR4s';
        $data = file_get_contents($query);
        $data = json_decode($data);
        return $data;
    }
    private static function createType($lat, $lng, $type_array, $radius) {
        $result = [];
        foreach ($type_array as $item) {
            $query = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location='.$lat.','.$lng.'&radius='.$radius.'&type='.$item.'&key=AIzaSyDEzKHEUbk3ocLvIgBGMOsJjguHEj0LR4s';
            $data = file_get_contents($query);
            $data = json_decode($data);
            foreach ($data->results as $val) {
                $result[] = $val;
            }
            if(isset($data->next_page_token)) {
                sleep(2);
                $page2 = MarkerController::getNextPage($data->next_page_token);
                foreach ($page2->results as $item1) {
                    $result[] = $item1;
                }
                if(isset($page2->next_page_token)) {
                    sleep(2);
                    $page3 = MarkerController::getNextPage($page2->next_page_token);
                    foreach ($page3->results as $item2) {
                        $result[] = $item2;
                    }
                }
            }
        }
        return $result;
    }
    public static function CreateMarkers($lat, $lng, $project_id) {
        $model = MapMarkers::find()->where(['project_id' => $project_id, 'creator' => 1])->asArray()->all();
        foreach ($model as $place) {
            $place_obj = MapMarkers::findOne($place['id']);
            $place_obj->delete();
        }
        $result = (object)[];
        $restaraunts = MarkerController::createType($lat, $lng,['restaurant'], 500);
        $culture = MarkerController::createType($lat, $lng, ['zoo', 'art_gallery', 'museum', 'night_club', 'movie_theater', 'library'], 500);
        $sport = MarkerController::createType($lat, $lng, ['gym', 'bowling_alley', 'stadium'], 1000);
        $nature = MarkerController::createType($lat, $lng, ['park'], 1000);
        foreach ($restaraunts as $restaraunt) {
            $model = new MapMarkers();
            $model->project_id = $project_id;
            $model->name = (string)$restaraunt->name;
            $model->lat = (string)$restaraunt->geometry->location->lat;
            $model->lng = (string)$restaraunt->geometry->location->lng;
            $model->address = (string)$restaraunt->vicinity;
            $model->type = 2;
            $model->creator = 1;
            $model->show_marker = 0;
            $model->save();
        }
        foreach ($culture as $cult) {
            $model = new MapMarkers();
            $model->project_id = $project_id;
            $model->name = (string)$cult->name;
            $model->lat = (string)$cult->geometry->location->lat;
            $model->lng = (string)$cult->geometry->location->lng;
            $model->address = (string)$cult->vicinity;
            $model->type = 1;
            $model->creator = 1;
            $model->show_marker = 0;
            $model->save();
        }
        foreach ($sport as $sp) {
            $model = new MapMarkers();
            $model->project_id = $project_id;
            $model->name = (string)$sp->name;
            $model->lat = (string)$sp->geometry->location->lat;
            $model->lng = (string)$sp->geometry->location->lng;
            $model->address = (string)$sp->vicinity;
            $model->type = 3;
            $model->creator = 1;
            $model->show_marker = 0;
            $model->save();
        }
        foreach ($nature as $nat) {
            $model = new MapMarkers();
            $model->project_id = $project_id;
            $model->name = (string)$nat->name;
            $model->lat = (string)$nat->geometry->location->lat;
            $model->lng = (string)$nat->geometry->location->lng;
            $model->address = (string)$nat->vicinity;
            $model->type = 4;
            $model->creator = 1;
            $model->show_marker = 0;
            $model->save();
        }
        return true;
    }
    public function actionClearNearPlaces() {
        $model = MapMarkers::find()->where(['project_id' => 1, 'creator' => 1])->asArray()->all();
        foreach ($model as $item) {
            $marker = MapMarkers::findOne($item['id']);
            $marker->delete();
        }
    }
    public function actionGetNearPlaces() {
        $result = (object)[];
        $data = (object)yii::$app->request->get();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        if($data->show == false) {
            $show = 0;
        } else {
            $show = 1;
        }
        $places = MapMarkers::find()->where(['project_id' => $data->project_id, 'creator' => 1, 'type' => $data->type])->asArray()->all();
        foreach ($places as $place) {
            $place_model = MapMarkers::findOne($place['id']);
            $place_model->show_marker = $show;
            $place_model->save();
        }
        $result->ok = 1;
        $result->type = $data->type;
        $result->places = $places;
        $result->show = $show;
        return json_encode($result);
    }
}
