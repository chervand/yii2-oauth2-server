<?php

namespace chervand\yii2\oauth2\server\components\Server;

use chervand\yii2\oauth2\server\components\Events\AuthorizationEvent;
use chervand\yii2\oauth2\server\components\Grant\RevokeGrant;
use chervand\yii2\oauth2\server\components\ResponseTypes\RevokeResponse;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use yii\base\Event;

class AuthorizationServer extends \League\OAuth2\Server\AuthorizationServer
{
    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseInterface $response
    )
    {
        $response = parent::respondToAccessTokenRequest($request, $response);

        if ($response instanceof ResponseInterface) {
            Event::trigger(
                $this,
                AuthorizationEvent::USER_AUTHENTICATION_SUCCEED,
                new AuthorizationEvent([
                    'request' => $request,
                    'response' => $response,
                ])
            );
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws OAuthServerException
     */
    public function respondToRevokeTokenRequest(
        ServerRequestInterface $request,
        ResponseInterface $response
    )
    {
        if (
            array_key_exists('revoke', $this->enabledGrantTypes) === true
            && $this->enabledGrantTypes['revoke'] instanceof RevokeGrant
        ) {

            $this->responseType = new RevokeResponse();

            /** @var RevokeGrant $revokeGrant */
            $revokeGrant = $this->enabledGrantTypes['revoke'];
            $revokeResponse = $revokeGrant->respondToRevokeTokenRequest($request, $this->getResponseType());

            if ($revokeResponse instanceof ResponseTypeInterface) {
                return $revokeResponse->generateHttpResponse($response);
            }

        }

        throw OAuthServerException::unsupportedGrantType();
    }
}
