<?php

namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\models\RefreshToken;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use yii\base\Component;

/**
 * Class RefreshTokenRepository
 * @package chervand\yii2\oauth2\server\components\Repositories
 */
class RefreshTokenRepository extends Component implements RefreshTokenRepositoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @return RefreshTokenEntityInterface|RefreshToken
     */
    public function getNewRefreshToken()
    {
        return new RefreshToken();
    }

    /**
     * {@inheritdoc}
     *
     * @param RefreshTokenEntityInterface|RefreshToken $refreshTokenEntity
     * @return RefreshTokenEntityInterface|RefreshToken
     * @throws OAuthServerException
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        if (
            $refreshTokenEntity instanceof RefreshToken
            && $refreshTokenEntity->save()
        ) {
            return $refreshTokenEntity;
        }

        throw OAuthServerException::serverError('Refresh token failure');
    }

    /**
     * {@inheritdoc}
     *
     * @throws OAuthServerException
     */
    public function revokeRefreshToken($tokenId)
    {
        $updated = RefreshToken::updateAll(
            ['status' => RefreshToken::STATUS_REVOKED],
            'identifier=:identifier',
            [':identifier' => $tokenId]
        );

        if ($updated < 1) {
            throw OAuthServerException::invalidRefreshToken('Cannot revoke the refresh token');
        }
    }

    /**
     * Check if the refresh token has been revoked.
     *
     * @param string $tokenId
     *
     * @return bool Return true if this token has been revoked
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        return RefreshToken::find()
                ->identifier($tokenId)
                ->active()
                ->exists() !== true;
    }
}
