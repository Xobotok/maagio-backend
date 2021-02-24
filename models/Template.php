<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "template".
 *
 * @property int $id
 * @property string $name
 * @property string $template_class
 *
 * @property ProjectTemplate[] $projectTemplates
 * @property Projects[] $projects
 */
class Template extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'template';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'template_class'], 'required'],
            [['name'], 'string', 'max' => 128],
            [['template_class'], 'string', 'max' => 400],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'template_class' => 'Template Class',
        ];
    }

    /**
     * Gets query for [[ProjectTemplates]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProjectTemplates()
    {
        return $this->hasMany(ProjectTemplate::className(), ['template_id' => 'id']);
    }

    /**
     * Gets query for [[Projects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProjects()
    {
        return $this->hasMany(Projects::className(), ['template_id' => 'id']);
    }
}
