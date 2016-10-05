<?php
namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\components\Entities\AccessTokenEntity;

class BearerTokenRepository extends AccessTokenRepository
{
    public function __construct($privateKey = null, $publicKey = null)
    {
        parent::__construct(
            AccessTokenEntity::TYPE_BEARER,
            $privateKey,
            $publicKey
        );
    }
}
