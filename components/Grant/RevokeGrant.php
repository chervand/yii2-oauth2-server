<?php
/**
 *
 */

namespace chervand\yii2\oauth2\server\components\Grant;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

class RevokeGrant extends AbstractGrant
{
    /**
     * @var \League\OAuth2\Server\CryptKey
     */
    protected $publicKey;


    /**
     * RevokeGrant constructor.
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     * @param CryptKey $publicKey
     */
    public function __construct(RefreshTokenRepositoryInterface $refreshTokenRepository, CryptKey $publicKey)
    {
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->setPublicKey($publicKey);
    }

    public function setPublicKey(CryptKey $key)
    {
        $this->publicKey = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'revoke';
    }

    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        \DateInterval $accessTokenTTL
    )
    {
        throw new \LogicException('This grant does not used this method');
    }

    /**
     * "Note: invalid tokens do not cause an error response since the client
     * cannot handle such an error in a reasonable way. Moreover, the
     * purpose of the revocation request, invalidating the particular token,
     * is already achieved."
     *
     * @see https://tools.ietf.org/html/rfc7009#section-2.2
     *
     * @param ServerRequestInterface $request
     * @param ResponseTypeInterface $response
     * @return mixed
     * @throws OAuthServerException
     */
    public function respondToRevokeTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $response
    )
    {
        $client = $this->validateClient($request);
        $this->invalidateToken($request, $client->getIdentifier());

        return $response;
    }

    /**
     * "If the server is unable to locate the token using
     * the given hint, it MUST extend its search across all of its
     * supported token types."
     *
     * @see https://tools.ietf.org/html/rfc7009#section-2.1
     *
     * @param ServerRequestInterface $request
     * @param $clientId
     */
    protected function invalidateToken(ServerRequestInterface $request, $clientId)
    {
        $tokenTypeHint = $this->getRequestParameter('token_type_hint', $request);

        $callStack = $tokenTypeHint == 'refresh_token'
            ? ['invalidateRefreshToken', 'invalidateAccessToken']
            : ['invalidateAccessToken', 'invalidateRefreshToken'];

        foreach ($callStack as $function) {
            if (call_user_func([$this, $function], $request, $clientId) === true) {
                break;
            }
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param $clientId
     * @return bool
     */
    protected function invalidateAccessToken(ServerRequestInterface $request, $clientId)
    {
        $accessToken = $this->getRequestParameter('token', $request);
        if (is_null($accessToken)) {
            throw OAuthServerException::invalidRequest('token');
        }

        try {
            $token = (new Parser())->parse($accessToken);
        } catch (\Exception $exception) {
            return false;
        }

        if ($token->verify(new Sha256(), $this->publicKey->getKeyPath()) === false) {
            throw OAuthServerException::accessDenied('Access token could not be verified');
        }

        if ($token->getClaim('aud') !== $clientId) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::REFRESH_TOKEN_CLIENT_FAILED, $request));
            throw OAuthServerException::invalidRefreshToken('Token is not linked to client');
        }

        $this->accessTokenRepository->revokeAccessToken($token->getClaim('jti'));

        return true;
    }

    /**
     * @param ServerRequestInterface $request
     * @param $clientId
     * @return bool
     */
    protected function invalidateRefreshToken(ServerRequestInterface $request, $clientId)
    {
        $encryptedRefreshToken = $this->getRequestParameter('token', $request);
        if (is_null($encryptedRefreshToken)) {
            throw OAuthServerException::invalidRequest('token');
        }

        try {
            $refreshToken = $this->decrypt($encryptedRefreshToken);
        } catch (\Exception $exception) {
            return false;
        }

        $refreshTokenData = json_decode($refreshToken, true);
        if ($refreshTokenData['client_id'] !== $clientId) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::REFRESH_TOKEN_CLIENT_FAILED, $request));
            throw OAuthServerException::invalidRefreshToken('Token is not linked to client');
        }

        $this->accessTokenRepository->revokeAccessToken($refreshTokenData['access_token_id']);
        $this->refreshTokenRepository->revokeRefreshToken($refreshTokenData['refresh_token_id']);

        return true;
    }
}
