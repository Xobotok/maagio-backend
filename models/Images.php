<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "images".
 *
 * @property int $id
 * @property string $image_name
 * @property string $image_link
 *
 * @property Floors[] $floors
 * @property GalleryPhotos[] $galleryPhotos
 */
class Images extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'images';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['image_name', 'image_link'], 'required'],
            [['image_name', 'image_link'], 'string', 'max' => 400],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'image_name' => 'Image Name',
            'image_link' => 'Image Link',
        ];
    }

    /**
     * Gets query for [[Floors]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFloors()
    {
        return $this->hasMany(Floors::className(), ['image_id' => 'id']);
    }

    /**
     * Gets query for [[GalleryPhotos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGalleryPhotos()
    {
        return $this->hasMany(GalleryPhotos::className(), ['image_id' => 'id']);
    }
}
