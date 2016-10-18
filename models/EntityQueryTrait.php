<?php
namespace chervand\yii2\oauth2\server\models;

use yii\db\ActiveRecord;

trait EntityQueryTrait
{
    /**
     * @param $identifier
     * @param string|null $tableName
     * @return \yii\db\ActiveQuery
     */
    public function identifier($identifier, $tableName = null)
    {
        if ($tableName === null) {
            /** @var ActiveRecord $modelClass */
            $modelClass = $this->modelClass;
            $tableName = $modelClass::tableName();
        }

        /** @var \yii\db\ActiveQuery $this */
        return $this->andWhere([
            $tableName . '.`identifier`' => $identifier
        ]);
    }
}
