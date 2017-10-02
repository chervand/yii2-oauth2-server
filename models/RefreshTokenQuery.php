<?php

namespace chervand\yii2\oauth2\server\models;

use yii\db\ActiveQuery;

class RefreshTokenQuery extends ActiveQuery
{
    /**
     * @return ClientQuery|ActiveQuery
     */
    public function active()
    {
        return $this->andWhere(['status' => RefreshToken::STATUS_ACTIVE]);
    }

    /**
     * @return ClientQuery|ActiveQuery
     */
    public function revoked()
    {
        return $this->andWhere(['<>', 'status', RefreshToken::STATUS_ACTIVE]);
    }
}
