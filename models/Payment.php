<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "payment".
 *
 * @property int $id
 * @property int $user_id
 * @property int $tariff_id
 * @property string $payment_start
 * @property string $payment_end
 * @property string $stripe_id
 *
 * @property Users $user
 * @property Tariff $tariff
 */
class Payment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'payment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'tariff_id', 'payment_end', 'stripe_id'], 'required'],
            [['user_id', 'tariff_id'], 'integer'],
            [['payment_start', 'payment_end'], 'safe'],
            [['stripe_id'], 'string', 'max' => 128],
            [['stripe_id'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'uid']],
            [['tariff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tariff::className(), 'targetAttribute' => ['tariff_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'tariff_id' => 'Tariff ID',
            'payment_start' => 'Payment Start',
            'payment_end' => 'Payment End',
            'stripe_id' => 'Stripe ID',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['uid' => 'user_id']);
    }

    /**
     * Gets query for [[Tariff]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTariff()
    {
        return $this->hasOne(Tariff::className(), ['id' => 'tariff_id']);
    }
}
