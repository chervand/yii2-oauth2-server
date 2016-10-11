<?php
namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\models\Client;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @param boolean $mustValidateGrant
     */
    public function getClientEntity(
        $clientIdentifier,
        $grantType,
        $clientSecret = null,
        $mustValidateSecret = true,
        $mustValidateGrant = true
    )
    {
        $clientEntity = Client::getDb()
            ->cache(function () use ($clientIdentifier, $grantType, $mustValidateGrant) {

                $query = Client::find();

                if ($mustValidateGrant === true) {
                    $query->grant($grantType);
                }

                return $query
                    ->active()
                    ->identifier($clientIdentifier)
                    ->one();
            });

        if (
            $mustValidateSecret !== true
            || (
                $clientEntity instanceof Client
                && Client::secretVerify($clientSecret, $clientEntity->secret)
            )
        ) {
            return $clientEntity;
        }

        return null;
    }
}
