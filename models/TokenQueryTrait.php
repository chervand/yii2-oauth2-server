<?php

namespace chervand\yii2\oauth2\server\models;

use yii\db\ActiveQuery;

trait TokenQueryTrait
{
    /**
     * @return ActiveQuery|static
     */
    public function active()
    {
        /** @var ActiveQuery $this */
        /** @var AccessToken|RefreshToken $modelClass */
        $modelClass = $this->modelClass;
        return $this->andWhere(['status' => $modelClass::STATUS_ACTIVE]);
    }

    /**
     * @return ClientQuery|ActiveQuery
     */
    public function revoked()
    {
        /** @var ActiveQuery $this */
        /** @var AccessToken|RefreshToken $modelClass */
        $modelClass = $this->modelClass;
        return $this->andWhere(['<>', 'status', $modelClass::STATUS_ACTIVE]);
    }
}
