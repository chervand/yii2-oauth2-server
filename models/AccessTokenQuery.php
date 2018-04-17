<?php
namespace chervand\yii2\oauth2\server\models;

use yii\db\ActiveQuery;

class AccessTokenQuery extends ActiveQuery
{
    use EntityQueryTrait, TokenQueryTrait;

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
    public function expired()
    {
        return $this;
    }
}
