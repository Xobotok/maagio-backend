<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "lot_info".
 *
 * @property int $id
 * @property int $project_id
 * @property string $unit_number
 * @property int $status 0 - available, 1 - reserved, 2 - sold
 * @property int $price
 * @property string $int_sq
 * @property string|null $ext_sq
 * @property int $bad
 * @property int $bath
 * @property int|null $bmr
 * @property int|null $parking
 * @property int|null $hoa
 * @property int|null $image_id
 *
 * @property Images $image
 * @property Projects $project
 */
class LotInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lot_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['project_id', 'unit_number', 'price', 'int_sq', 'bad', 'bath'], 'required'],
            [['project_id', 'status', 'price', 'bad', 'bath', 'bmr', 'parking', 'hoa', 'image_id'], 'integer'],
            [['unit_number'], 'string', 'max' => 128],
            [['int_sq', 'ext_sq'], 'string', 'max' => 256],
            [['image_id'], 'exist', 'skipOnError' => true, 'targetClass' => Images::className(), 'targetAttribute' => ['image_id' => 'id']],
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
            'unit_number' => 'Unit Number',
            'status' => 'Status',
            'price' => 'Price',
            'int_sq' => 'Int Sq',
            'ext_sq' => 'Ext Sq',
            'bad' => 'Bad',
            'bath' => 'Bath',
            'bmr' => 'Bmr',
            'parking' => 'Parking',
            'hoa' => 'Hoa',
            'image_id' => 'Image ID',
        ];
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
