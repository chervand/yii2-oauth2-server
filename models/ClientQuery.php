<?php
namespace chervand\yii2\oauth2\server\models;

use yii\db\ActiveQuery;

class ClientQuery extends ActiveQuery
{
    use EntityQueryTrait;


    /**
     * @return ClientQuery|ActiveQuery
     */
    public function confidential()
    {
        return $this->andWhere([
            'not', [Client::tableName() . '.`secret`' => null]
        ]);
    }

    /**
     * @param $grantType
     * @return ClientQuery|ActiveQuery
     */
    public function grant($grantType)
    {
        if (!is_numeric($grantType)) {
            $grantType = Client::getGrantTypeId($grantType, -999);
        }

        return $this->andWhere([
            Client::tableName() . '.`grant_type`' => $grantType
        ]);
    }

    /**
     * @return ClientQuery|ActiveQuery
     */
    public function active()
    {
        return $this->andWhere([
            Client::tableName() . '.`status`' => Client::STATUS_ACTIVE
        ]);
    }
}
