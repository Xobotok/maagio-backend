<?php

namespace app\controllers;
require_once 'Constants.php';
use app\models\Projects;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class AppController extends BaseController
{
    public static function createManifest($project_id) {
        $result = (object)[];

        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $url = explode('?', $url);
        $url = $url[0];
        $project = Projects::find()->where(['special_link' => $project_id])->one();
        $url = $url . '/manifests/' . $project_id.'.json';
        $manifest = '{
  "name": "'.$project->attributes['name'].'",
  "short_name": "maagio",
  "theme_color": "#4DBA87",
  "icons": [
    {
      "src": "./img/icons/android-chrome-192x192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "./img/icons/android-chrome-512x512.png",
      "sizes": "512x512",
      "type": "image/png"
    },
    {
      "src": "./img/icons/android-chrome-maskable-192x192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "maskable"
    },
    {
      "src": "./img/icons/android-chrome-maskable-512x512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "maskable"
    }
  ],
  "start_url": "'.FRONTEND_URL.'/show/'.$project_id.'",
  "display": "standalone",
  "background_color": "#000000"
}';
// создаем новый файл
        if( ! is_dir( 'manifests' ) ) mkdir( 'manifests', 0777 );
        $file = fopen('manifests/'.$project_id.'.json', 'w');
// и записываем туда данные
        $write = fwrite($file, $manifest);
        $result->ok = 1;
        $result->manifest_url = $url;
        return json_encode($result);
    }
    public function actionChangeManifest()
    {
        $result = (object)[];
        $data = (object)yii::$app->request->get();
        $project_id = explode('/',$data->href);
        $project_id = $project_id[count($project_id) - 1];
        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $url = explode('?', $url);
        $url = $url[0];
        $url = $url . '/manifests/' . $project_id.'.json';
        $manifest = '{
  "name": "maagio",
  "short_name": "maagio",
  "theme_color": "#4DBA87",
  "icons": [
    {
      "src": "./img/icons/android-chrome-192x192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "./img/icons/android-chrome-512x512.png",
      "sizes": "512x512",
      "type": "image/png"
    },
    {
      "src": "./img/icons/android-chrome-maskable-192x192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "maskable"
    },
    {
      "src": "./img/icons/android-chrome-maskable-512x512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "maskable"
    }
  ],
  "start_url": "'.FRONTEND_URL.'/'.$project_id.'",
  "display": "standalone",
  "background_color": "#000000"
}';
// создаем новый файл
        if( ! is_dir( 'manifests' ) ) mkdir( 'manifests', 0777 );
        $file = fopen('manifests/'.$project_id.'.json', 'w');
// и записываем туда данные
        $write = fwrite($file, $manifest);
        $result->ok = 1;
        $result->manifest_url = $url;
        return json_encode($result);
    }
}
