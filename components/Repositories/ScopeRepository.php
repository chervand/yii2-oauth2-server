<?php

namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\models\Client;
use chervand\yii2\oauth2\server\models\Scope;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use yii\base\Component;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class ScopeRepository extends Component implements ScopeRepositoryInterface
{

    /**
     * Return information about a scope.
     *
     * @param string $identifier The scope identifier
     *
     * @return ScopeEntityInterface|ActiveRecord
     */
    public function getScopeEntityByIdentifier($identifier)
    {
        return Scope::getDb()
            ->cache(function () use ($identifier) {
                return Scope::find()
                    ->identifier($identifier)
                    ->one();
            });
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {

        /** @var Client $clientEntity */
        return $clientEntity::getDb()
            ->cache(function () use ($scopes, $grantType, $clientEntity, $userIdentifier) {

                $permittedScopes = $clientEntity->getRelatedScopes(

                    function (ActiveQuery $query) use ($scopes, $grantType, $userIdentifier) {

                        if (empty($scopes) === true) {
                            $query->andWhere(['is_default' => true]);
                        }

                        // common and assigned to user
                        $query->andWhere(['or', ['user_id' => null], ['user_id' => $userIdentifier]]);

                        // common and grant-specific
                        $query->andWhere([
                            'or',
                            ['grant_type' => null],
                            ['grant_type' => Client::getGrantTypeId($grantType)]
                        ]);

                    }
                );

                if (empty($scopes) === false) {
                    $permittedScopes->andWhere(['in', 'identifier', $scopes]);
                }

                return $permittedScopes->all();
            });

    }
}
