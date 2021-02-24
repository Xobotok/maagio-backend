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
 * @property int $published
 * @property int $house_type
 * @property int $template_id
 *
 * @property Floors[] $floors
 * @property Galleries[] $galleries
 * @property LotInfo[] $lotInfos
 * @property MapMarkers[] $mapMarkers
 * @property Maps[] $maps
 * @property ProjectTemplate[] $projectTemplates
 * @property Users $user
 * @property Images $projectLogo
 * @property HouseTypes $houseType
 * @property Template $template
 * @property Units[] $units
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
            [['user_id', 'project_logo', 'published', 'house_type', 'template_id'], 'integer'],
            [['name'], 'string', 'max' => 128],
            [['special_link'], 'string', 'max' => 400],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'uid']],
            [['project_logo'], 'exist', 'skipOnError' => true, 'targetClass' => Images::className(), 'targetAttribute' => ['project_logo' => 'id']],
            [['house_type'], 'exist', 'skipOnError' => true, 'targetClass' => HouseTypes::className(), 'targetAttribute' => ['house_type' => 'id']],
            [['template_id'], 'exist', 'skipOnError' => true, 'targetClass' => Template::className(), 'targetAttribute' => ['template_id' => 'id']],
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
            'published' => 'Published',
            'house_type' => 'House Type',
            'template_id' => 'Template ID',
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
     * Gets query for [[LotInfos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLotInfos()
    {
        return $this->hasMany(LotInfo::className(), ['project_id' => 'id']);
    }

    /**
     * Gets query for [[MapMarkers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMapMarkers()
    {
        return $this->hasMany(MapMarkers::className(), ['project_id' => 'id']);
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
     * Gets query for [[ProjectTemplates]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProjectTemplates()
    {
        return $this->hasMany(ProjectTemplate::className(), ['project_id' => 'id']);
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

    /**
     * Gets query for [[HouseType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHouseType()
    {
        return $this->hasOne(HouseTypes::className(), ['id' => 'house_type']);
    }

    /**
     * Gets query for [[Template]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTemplate()
    {
        return $this->hasOne(Template::className(), ['id' => 'template_id']);
    }

    /**
     * Gets query for [[Units]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUnits()
    {
        return $this->hasMany(Units::className(), ['project_id' => 'id']);
    }
}
