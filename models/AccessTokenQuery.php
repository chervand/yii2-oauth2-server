<?php
namespace chervand\yii2\oauth2\server\models;

use yii\db\ActiveQuery;

class AccessTokenQuery extends ActiveQuery
{
    use EntityQueryTrait;

    /**
     * @param integer $type
     * @return ClientQuery|ActiveQuery
     */
    public function type($type)
    {
        return $this->andWhere(['type' => $type]);
    }

    /**
     * @return ClientQuery|ActiveQuery
     */
    public function active()
    {
        return $this->andWhere(['status' => AccessToken::STATUS_ACTIVE]);
    }

    /**
     * @return ClientQuery|ActiveQuery
     */
    public function revoked()
    {
        return $this->andWhere(['<>','status', AccessToken::STATUS_ACTIVE]);
    }

    /**
     * @return ClientQuery|ActiveQuery
     */
    public function expired()
    {
        return $this;
    }
}
