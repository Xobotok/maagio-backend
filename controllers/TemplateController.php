<?php

namespace app\controllers;
require_once 'Constants.php';
use app\models\Projects;
use app\models\Template;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class TemplateController extends BaseController
{
    public function actionTakeTemplates() {
        $templates = Template::find()->asArray()->all();
        $result = (object)[];
        $result->ok = 1;
        $result->templates = $templates;
        return json_encode($result);
    }
}
