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

    public function actionUpdateMap()
    {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $datamap = json_decode($data->map);
        $map = Maps::find()->where(['project_id' => $data->project_id])->asArray()->one();
        $map = Maps::findOne($map['id']);
        if($map == null) {
            $map = new Maps();
            $map->project_id = $data->project_id;
        }
        if($map->lat == (string)$datamap->lat && $map->lng == (string)$datamap->lng) {
            $map->lat = (string)$datamap->lat;
            $map->lng = (string)$datamap->lng;
            $map->save();
        } else {
            $map->lat = (string)$datamap->lat;
            $map->lng = (string)$datamap->lng;
            $map->save();
            $markers = MapMarkers::find()->where(['project_id' => $data->project_id, 'creator' => 1])->asArray()->all();
            foreach ($markers as $marker) {
                $map_marker = MapMarkers::findOne($marker['id']);
                $map_marker->delete();
            }
          MarkerController::CreateMarkers($map->lat, $map->lng, $data->project_id);
        }
        $result->ok = 1;
        $result->map = $map;
        return json_encode($result);
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
