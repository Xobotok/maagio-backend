<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "galleries".
 *
 * @property int $id
 * @property string $name
 * @property int $project_id
 *
 * @property Projects $project
 * @property GalleryPhotos[] $galleryPhotos
 */
class Galleries extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'galleries';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'project_id'], 'required'],
            [['project_id'], 'integer'],
            [['name'], 'string', 'max' => 128],
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
            'name' => 'Name',
            'project_id' => 'Project ID',
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

    /**
     * Gets query for [[GalleryPhotos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGalleryPhotos()
    {
        return $this->hasMany(GalleryPhotos::className(), ['gallery_id' => 'id']);
    }
}
