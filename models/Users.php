<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $uid
 * @property string $email
 * @property string $password
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $company
 * @property string|null $login_token
 * @property string $last_sign_in_at
 * @property string|null $last_sign_in_ip
 * @property string|null $confirmation_token
 * @property string $confirmation_sent_at
 * @property string|null $confirmed_at
 * @property int $confirmed
 *
 * @property Projects[] $projects
 */
class Users extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'password', 'first_name'], 'required'],
            [['email', 'first_name', 'last_name', 'company', 'last_sign_in_ip'], 'string'],
            [['last_sign_in_at', 'confirmation_sent_at', 'confirmed_at'], 'safe'],
            [['confirmed'], 'integer'],
            [['password', 'login_token', 'confirmation_token'], 'string', 'max' => 400],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'uid' => 'Uid',
            'email' => 'Email',
            'password' => 'Password',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'company' => 'Company',
            'login_token' => 'Login Token',
            'last_sign_in_at' => 'Last Sign In At',
            'last_sign_in_ip' => 'Last Sign In Ip',
            'confirmation_token' => 'Confirmation Token',
            'confirmation_sent_at' => 'Confirmation Sent At',
            'confirmed_at' => 'Confirmed At',
            'confirmed' => 'Confirmed',
        ];
    }

    /**
     * Gets query for [[Projects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProjects()
    {
        return $this->hasMany(Projects::className(), ['user_id' => 'uid']);
    }
}
