<?php

namespace chervand\yii2\oauth2\server\components\Repositories;

use chervand\yii2\oauth2\server\models\RefreshToken;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use yii\base\Component;
use yii\caching\TagDependency;

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
        if ($refreshTokenEntity instanceof RefreshToken) {
            $refreshTokenEntity->setAttribute(
                'expired_at',
                $refreshTokenEntity->getExpiryDateTime()->getTimestamp()
            );
            if ($refreshTokenEntity->save()) {
                return $refreshTokenEntity;
            }
        }

        throw OAuthServerException::serverError('Refresh token failure');
    }

    /**
     * {@inheritdoc}
     * @throws \Throwable
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        $token = $this->getCachedToken($tokenId);
        return $token instanceof RefreshToken === false;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeRefreshToken($tokenId)
    {
        $token = $this->getCachedToken($tokenId);

        if ($token instanceof RefreshToken) {

            $token->updateAttributes([
                'status' => RefreshToken::STATUS_REVOKED,
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
     * @return RefreshToken|null
     */
    protected function getCachedToken($tokenId)
    {
        try {
            $token = RefreshToken::getDb()
                ->cache(
                    function () use ($tokenId) {
                        return RefreshToken::find()
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
