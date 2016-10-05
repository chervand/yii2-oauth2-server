<?php
namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\components\Entities\ClientEntity;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function getClientEntity($clientIdentifier, $grantType, $clientSecret = null, $mustValidateSecret = true)
    {
        $clientEntity = ClientEntity::find()
            ->identifier($clientIdentifier)
            ->grant($grantType)
            ->active()
            ->one();

        if (
            $mustValidateSecret !== true
            || (
                $clientEntity instanceof ClientEntity
                && ClientEntity::secretVerify($clientSecret, $clientEntity->secret)
            )
        ) {
            return $clientEntity;
        }

        return null;
    }
}
