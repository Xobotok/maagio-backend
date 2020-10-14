<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "map_markers".
 *
 * @property int $id
 * @property int $project_id
 * @property string|null $address
 * @property string|null $name
 * @property string|null $description
 * @property string $lat
 * @property string $lng
 * @property int $type 0 - тип неопределён, 1 - культура, 2 - рестораны, 3 - спорт, 4 - природа.
 * @property int $creator 0 - пользовательский маркер, 1 - создан автоматически
 * @property int $show_marker 0 - не показывать, 1 - показывать
 *
 * @property Projects $project
 */
class MapMarkers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'map_markers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['project_id', 'lat', 'lng'], 'required'],
            [['project_id', 'type', 'creator', 'show_marker'], 'integer'],
            [['address', 'description'], 'string'],
            [['name', 'lat', 'lng'], 'string', 'max' => 128],
            [['project_id'], 'exist', 'skipOnError' => true, 'targetClass' => Projects::className(), 'targetAttribute' => ['project_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'project_id' => 'Project ID',
            'address' => 'Address',
            'name' => 'Name',
            'description' => 'Description',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'type' => 'Type',
            'creator' => 'Creator',
            'show_marker' => 'Show Marker',
        ];
    }

    /**
     * Gets query for [[Project]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Projects::className(), ['id' => 'project_id']);
    }
}
