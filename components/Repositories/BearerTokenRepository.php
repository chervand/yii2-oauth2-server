<?php

namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\models\AccessToken;

class BearerTokenRepository extends AccessTokenRepository
{
    public function __construct($privateKey = null, $publicKey = null)
    {
        parent::__construct(
            AccessToken::TYPE_BEARER,
            $privateKey,
            $publicKey
        );
    }
}
