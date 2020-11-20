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
        $query = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $marker->lat . ',' . $marker->lng . '&key=' . GOOGLE_API_KEY;
        $query = file_get_contents($query);
        $query = json_decode($query);
        $address = $query->results[0]->formatted_address;
        $model = new MapMarkers();
        $model->lat = (string)$marker->lat;
        $model->lng = (string)$marker->lng;
        $model->project_id = $data->project_id;
        $model->type = (int)$marker->type;
        if (isset($marker->name) && $marker->name != null) {
            $model->name = (string)$marker->name;
        }
        if (isset($marker->description) && $marker->description != null) {
            $model->description = (string)$marker->description;
        }
        if ($address != null) {
            $model->address = (string)$address;
        }
        $model->save();
        if ($model->id != null) {
            $result->ok = 1;
            $result->marker = $model->attributes;
        } else {
            $result->ok = 0;
            $result->message = 'Something wrong';
            $result->marker = $model;
        }
        return json_encode($result);
    }

    public function actionTakePlaceById()
    {
        $result = (object)[];
        $data = (object)yii::$app->request->get();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $query = 'https://maps.googleapis.com/maps/api/geocode/json?place_id=' . $data->place_id . '&key=' . GOOGLE_API_KEY;
        $query = file_get_contents($query);
        $query = json_decode($query);
        if (count($query->results) > 0) {
            $result->place = $query->results[0];
        } else {
            $result->place = '';
        }
        $result->ok = 1;
        return json_encode($result);
    }

    public static function getMarkers($project_id)
    {
        $result['user_markers'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 0])->asArray()->all();
        $result['culture'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 1, 'type' => 1])->asArray()->all();
        $result['restaurant'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 1, 'type' => 2])->asArray()->all();
        $result['sport'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 1, 'type' => 3])->asArray()->all();
        $result['nature'] = MapMarkers::find()->where(['project_id' => $project_id, 'show_marker' => 1, 'creator' => 1, 'type' => 4])->asArray()->all();
        return $result;
    }

    public function actionDeleteMarker()
    {
        $result = (object)[];
        $data = (object)yii::$app->request->post();
        $authorisation = $this->checkAuthorisation($data->user_id, $data->token);
        if ($authorisation->ok === 0) {
            return json_encode($authorisation);
        }
        $marker = MapMarkers::findOne($data->marker_id);
        if ($marker != null) {
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
}
