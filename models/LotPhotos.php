<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "lot_photos".
 *
 * @property int $id
 * @property int $lot_id
 * @property int $image_id
 * @property string $image_link
 *
 * @property LotInfo $lot
 * @property Images $image
 */
class LotPhotos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lot_photos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lot_id', 'image_id', 'image_link'], 'required'],
            [['lot_id', 'image_id'], 'integer'],
            [['image_link'], 'string', 'max' => 400],
            [['lot_id'], 'exist', 'skipOnError' => true, 'targetClass' => LotInfo::className(), 'targetAttribute' => ['lot_id' => 'id']],
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
            'lot_id' => 'Lot ID',
            'image_id' => 'Image ID',
            'image_link' => 'Image Link',
        ];
    }

    /**
     * Gets query for [[Lot]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLot()
    {
        return $this->hasOne(LotInfo::className(), ['id' => 'lot_id']);
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
