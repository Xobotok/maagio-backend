<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "units".
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $floor_id
 * @property int $unit_number
 * @property int $status 0 - available, 1 - reserved, 2 - sold
 * @property int $price
 * @property int $int_sq
 * @property int|null $ext_sq
 * @property int $bad
 * @property int $bath
 * @property int|null $bmr
 * @property int|null $parking
 * @property int|null $hoa
 * @property string|null $mark_x
 * @property string|null $mark_y
 * @property int|null $image_id
 *
 * @property Images $image
 * @property Projects $project
 * @property Floors $floor
 */
class Units extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'units';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['project_id', 'unit_number', 'price', 'int_sq', 'bad', 'bath'], 'required'],
            [['project_id', 'floor_id', 'unit_number', 'status', 'price', 'int_sq', 'ext_sq', 'bad', 'bath', 'bmr', 'parking', 'hoa', 'image_id'], 'integer'],
            [['mark_x', 'mark_y'], 'string', 'max' => 128],
            [['image_id'], 'exist', 'skipOnError' => true, 'targetClass' => Images::className(), 'targetAttribute' => ['image_id' => 'id']],
            [['project_id'], 'exist', 'skipOnError' => true, 'targetClass' => Projects::className(), 'targetAttribute' => ['project_id' => 'id']],
            [['floor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Floors::className(), 'targetAttribute' => ['floor_id' => 'id']],
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
            'floor_id' => 'Floor ID',
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
            'mark_x' => 'Mark X',
            'mark_y' => 'Mark Y',
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

    /**
     * Gets query for [[Floor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFloor()
    {
        return $this->hasOne(Floors::className(), ['id' => 'floor_id']);
    }
}
