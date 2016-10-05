<?php
namespace chervand\yii2\oauth2\server\models;

use yii\db\ActiveQuery;

class ClientQuery extends ActiveQuery
{
    use EntityQueryTrait;

    /**
     * @param $grantType
     * @return ClientQuery|ActiveQuery
     */
    public function grant($grantType)
    {
        if (!is_numeric($grantType)) {
            $grantType = Client::getGrantTypeId($grantType, -999);
        }

        return $this->andWhere(['grant_type' => $grantType]);
    }

    /**
     * @return ClientQuery|ActiveQuery
     */
    public function active()
    {
        return $this->andWhere(['status' => Client::STATUS_ACTIVE]);
    }
}
