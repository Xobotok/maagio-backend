<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "unit_mark".
 *
 * @property int $id
 * @property int $unit_id
 * @property string $x
 * @property string $y
 * @property string $width
 * @property string $height
 * @property string $font_size
 *
 * @property Units $unit
 */
class UnitMark extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'unit_mark';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['unit_id', 'x', 'y', 'width', 'height'], 'required'],
            [['unit_id'], 'integer'],
            [['x', 'y', 'width', 'height', 'font_size'], 'string', 'max' => 128],
            [['unit_id'], 'exist', 'skipOnError' => true, 'targetClass' => Units::className(), 'targetAttribute' => ['unit_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'unit_id' => 'Unit ID',
            'x' => 'X',
            'y' => 'Y',
            'width' => 'Width',
            'height' => 'Height',
            'font_size' => 'Font Size',
        ];
    }

    /**
     * Gets query for [[Unit]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUnit()
    {
        return $this->hasOne(Units::className(), ['id' => 'unit_id']);
    }
}
