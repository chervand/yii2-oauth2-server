<?php

namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\models\AccessToken;
use chervand\yii2\oauth2\server\models\Client;
use chervand\yii2\oauth2\server\models\Scope;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\CryptTrait;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use yii\base\InvalidConfigException;

abstract class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    use CryptTrait;

    /**
     * @var integer
     */
    private $_tokenTypeId;

    public function __construct($tokenTypeId, $encryptionKey = null)
    {
        if (!in_array($tokenTypeId, [AccessToken::TYPE_BEARER, AccessToken::TYPE_MAC])) {
            throw new InvalidConfigException('Unknown token type.');
        }

        $this->_tokenTypeId = $tokenTypeId;
        $this->setEncryptionKey($encryptionKey);
    }

    /**
     * Create a new access token instance.
     *
     * @param ClientEntityInterface|Client $clientEntity
     * @param ScopeEntityInterface[] $scopes
     * @param mixed $userIdentifier
     * @return AccessTokenEntityInterface
     * @throws OAuthServerException
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $token = new AccessToken();
        $token->client_id = $clientEntity->id;
        $token->type = $clientEntity->token_type;

        if (!$token->validate()) {
            throw OAuthServerException::serverError('Token creation failed');
        }

        return $token;
    }

    /**
     * Persists a new access token to permanent storage.
     *
     * @param AccessTokenEntityInterface $accessTokenEntity
     * @return AccessTokenEntityInterface
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        if ($accessTokenEntity instanceof AccessToken) {
            if ($this->_tokenTypeId === AccessToken::TYPE_MAC) {
                $accessTokenEntity->type = AccessToken::TYPE_MAC;
                $accessTokenEntity->mac_key = $this->encrypt($accessTokenEntity->getIdentifier());
            }
            $accessTokenEntity->expired_at = $accessTokenEntity->getExpiryDateTime()->getTimestamp();


            // TODO[d6, 14/10/16]: transaction
            if ($accessTokenEntity->save()) {
                foreach ($accessTokenEntity->getScopes() as $scope) {
                    if ($scope instanceof Scope) {
                        $accessTokenEntity->link('grantedScopes', $scope);
                    }
                }
            }
        }

        return $accessTokenEntity;
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param string $tokenId
     * @return bool Return true if this token has been revoked
     */
    public function isAccessTokenRevoked($tokenId)
    {
        $token = AccessToken::getDb()
            ->cache(function () use ($tokenId) {
                return AccessToken::find()
                    ->identifier($tokenId)
                    ->active()
                    ->one();
            });

        if (
            $token instanceof AccessToken
            && $token->type !== $this->_tokenTypeId
        ) {
            $this->revokeAccessToken($tokenId);
            return true;
        }

        return $token instanceof AccessToken === false;
    }

    /**
     * Revoke an access token.
     *
     * @param string $tokenId
     * @return int
     */
    public function revokeAccessToken($tokenId)
    {
        $token = AccessToken::find()->andWhere([
            'identifier' => $tokenId,
        ])->active()->one();

        if ($token instanceof AccessToken) {
            return $token->updateAttributes(['status' => AccessToken::STATUS_REVOKED]);
        }

        return null;
    }
}
