<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
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
        $map->lat = (string)$datamap->lat;
        $map->lng = (string)$datamap->lng;
        $map->save();
        $result->ok = 1;
        $result->map = $map;
        return json_encode($result);
    }
}
