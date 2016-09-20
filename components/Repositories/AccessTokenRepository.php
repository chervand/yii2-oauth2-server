<?php
namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\components\Entities\AccessTokenEntity;
use chervand\yii2\oauth2\server\components\ResponseTypes\MacTokenResponse;
use chervand\yii2\oauth2\server\models\AccessToken;
use chervand\yii2\oauth2\server\models\Client;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\CryptTrait;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    use CryptTrait;

    /**
     * @var ResponseTypeInterface
     */
    private $_responseType;

    public function __construct(
        ResponseTypeInterface &$responseType = null,
        CryptKey $privateKey = null,
        CryptKey $publicKey = null
    )
    {
        $this->_responseType = $responseType;
        if ($privateKey instanceof CryptKey) {
            $this->setPrivateKey($privateKey);
        }
        if ($publicKey instanceof CryptKey) {
            $this->setPublicKey($publicKey);
        }
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
        $token = new AccessTokenEntity();
        $token->client_id = $clientEntity->id;

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
            if ($this->_responseType instanceof MacTokenResponse) {
                $accessTokenEntity->type = AccessToken::TYPE_MAC;
                $accessTokenEntity->mac_key = $this->encrypt($accessTokenEntity->getIdentifier());
            }
            $accessTokenEntity->save();
        }

        return $accessTokenEntity;
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

    /**
     * Check if the access token has been revoked.
     *
     * @param string $tokenId
     * @return bool Return true if this token has been revoked
     */
    public function isAccessTokenRevoked($tokenId)
    {
        $token = AccessToken::find()->andWhere([
            'identifier' => $tokenId,
        ])->one();

        if ($token instanceof AccessToken) {
            return $token->status == AccessToken::STATUS_REVOKED;
        }

        return false;
    }
}
