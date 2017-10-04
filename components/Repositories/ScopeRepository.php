<?php
namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\models\Client;
use chervand\yii2\oauth2\server\models\Scope;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use yii\base\Component;
use yii\db\ActiveRecord;

class ScopeRepository extends Component  implements ScopeRepositoryInterface
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
     * Given a client, grant type and optional user identifier validate the set of scopes requested are valid and optionally
     * append additional scopes or remove requested scopes.
     *
     * @param ScopeEntityInterface[] $scopes
     * @param string $grantType
     * @param ClientEntityInterface|Client $clientEntity
     * @param null|string $userIdentifier
     *
     * @return ScopeEntityInterface[]
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    )
    {
        if (empty($scopes)) {
            return $clientEntity->permittedScopes;
        }

        return array_filter($scopes, function (Scope $scope) use ($clientEntity) {
            foreach ($clientEntity->permittedScopes as $permittedScope) {
                if ($permittedScope->identifier === $scope->identifier) {
                    return $scope;
                }
            }
            return null;
        });
    }
}
