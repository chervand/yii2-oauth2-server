<?php

namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\models\AccessToken;
use chervand\yii2\oauth2\server\models\Client;
use chervand\yii2\oauth2\server\models\Scope;
use League\OAuth2\Server\CryptTrait;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;

abstract class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    use CryptTrait;

    /**
     * @var integer
     */
    private $_tokenTypeId;

    /**
     * AccessTokenRepository constructor.
     * @param $tokenTypeId
     * @param null|string $encryptionKey
     * @throws InvalidConfigException
     */
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
     * {@inheritdoc}
     * @throws \Throwable
     */
    public function isAccessTokenRevoked($tokenId)
    {
        $token = $this->getCachedToken($tokenId);

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
     * {@inheritdoc}
     */
    public function revokeAccessToken($tokenId)
    {
        $token = $this->getCachedToken($tokenId);

        if ($token instanceof AccessToken) {

            $token->updateAttributes([
                'status' => AccessToken::STATUS_REVOKED,
                'updated_at' => time(),
            ]);

            TagDependency::invalidate(
                \Yii::$app->cache,
                static::class
            );

        }
    }

    /**
     * @param $tokenId
     * @return AccessToken|null
     */
    protected function getCachedToken($tokenId)
    {
        try {
            $token = AccessToken::getDb()
                ->cache(
                    function () use ($tokenId) {
                        return AccessToken::find()
                            ->identifier($tokenId)
                            ->active()->one();
                    },
                    null,
                    new TagDependency(['tags' => static::class])
                );
        } catch (\Throwable $exception) {
            $token = null;
        }

        return $token;
    }
}
