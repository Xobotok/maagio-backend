<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "subscribes".
 *
 * @property int $id
 * @property int $user_id
 * @property string $subscribe_stripe_id
 * @property string $subscribe_start
 * @property string $subscribe_end
 *
 * @property Users $user
 */
class Subscribes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscribes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'subscribe_stripe_id', 'subscribe_start', 'subscribe_end'], 'required'],
            [['user_id'], 'integer'],
            [['subscribe_start', 'subscribe_end'], 'safe'],
            [['subscribe_stripe_id'], 'string', 'max' => 128],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'uid']],
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
            'subscribe_stripe_id' => 'Subscribe Stripe ID',
            'subscribe_start' => 'Subscribe Start',
            'subscribe_end' => 'Subscribe End',
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
}
