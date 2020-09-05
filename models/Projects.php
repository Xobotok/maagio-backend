<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "projects".
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property int|null $project_logo
 * @property string|null $special_link
 *
 * @property Floors[] $floors
 * @property Galleries[] $galleries
 * @property Maps[] $maps
 * @property Users $user
 * @property Images $projectLogo
 */
class Projects extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'projects';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'name'], 'required'],
            [['user_id', 'project_logo'], 'integer'],
            [['name'], 'string', 'max' => 128],
            [['special_link'], 'string', 'max' => 400],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'uid']],
            [['project_logo'], 'exist', 'skipOnError' => true, 'targetClass' => Images::className(), 'targetAttribute' => ['project_logo' => 'id']],
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
            'name' => 'Name',
            'project_logo' => 'Project Logo',
            'special_link' => 'Special Link',
        ];
    }

    /**
     * Gets query for [[Floors]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFloors()
    {
        return $this->hasMany(Floors::className(), ['project_id' => 'id']);
    }

    /**
     * Gets query for [[Galleries]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGalleries()
    {
        return $this->hasMany(Galleries::className(), ['project_id' => 'id']);
    }

    /**
     * Gets query for [[Maps]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMaps()
    {
        return $this->hasMany(Maps::className(), ['project_id' => 'id']);
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
     * Gets query for [[ProjectLogo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProjectLogo()
    {
        return $this->hasOne(Images::className(), ['id' => 'project_logo']);
    }
}
