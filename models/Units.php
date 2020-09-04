<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "units".
 *
 * @property int $id
 * @property int $floor_id
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
 *
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
            [['floor_id', 'unit_number', 'price', 'int_sq', 'bad', 'bath'], 'required'],
            [['floor_id', 'unit_number', 'status', 'price', 'int_sq', 'ext_sq', 'bad', 'bath', 'bmr', 'parking', 'hoa'], 'integer'],
            [['mark_x', 'mark_y'], 'string', 'max' => 128],
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
        ];
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
