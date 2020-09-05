<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "gallery_photos".
 *
 * @property int $id
 * @property int $gallery_id
 * @property int $image_id
 * @property int|null $number
 *
 * @property Galleries $gallery
 * @property Images $image
 */
class GalleryPhotos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gallery_photos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gallery_id', 'image_id'], 'required'],
            [['gallery_id', 'image_id', 'number'], 'integer'],
            [['gallery_id'], 'exist', 'skipOnError' => true, 'targetClass' => Galleries::className(), 'targetAttribute' => ['gallery_id' => 'id']],
            [['image_id'], 'exist', 'skipOnError' => true, 'targetClass' => Images::className(), 'targetAttribute' => ['image_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'gallery_id' => 'Gallery ID',
            'image_id' => 'Image ID',
            'number' => 'Number',
        ];
    }

    /**
     * Gets query for [[Gallery]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGallery()
    {
        return $this->hasOne(Galleries::className(), ['id' => 'gallery_id']);
    }

    /**
     * Gets query for [[Image]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(Images::className(), ['id' => 'image_id']);
    }
}
