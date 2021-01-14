<?php

namespace app\controllers;

use app\models\Floors;
use app\models\Images;
use app\models\LotInfo;
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
      $result = $model->attributes;
      $result['image'] = Images::findOne($model->attributes['image_id'])->image_link;
      return $result;
  }
}
