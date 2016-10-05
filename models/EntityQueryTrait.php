<?php
namespace chervand\yii2\oauth2\server\models;

trait EntityQueryTrait
{
    /**
     * @param $identifier
     * @return \yii\db\ActiveQuery
     */
    public function identifier($identifier)
    {
        /** @var \yii\db\ActiveQuery $this */
        return $this->andWhere([
            'identifier' => $identifier
        ]);
    }
}
