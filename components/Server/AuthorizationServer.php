<?php

namespace chervand\yii2\oauth2\server\components\Server;

use chervand\yii2\oauth2\server\components\Events\AuthorizationEvent;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use yii\base\Event;

class AuthorizationServer extends \League\OAuth2\Server\AuthorizationServer
{
    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {

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

        } catch (OAuthServerException $exception) {
            throw $exception;
        }
    }
}
