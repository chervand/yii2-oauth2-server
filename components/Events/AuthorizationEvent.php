<?php

namespace chervand\yii2\oauth2\server\components\Events;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use yii\base\Event;
use yii\helpers\Json;

class AuthorizationEvent extends Event
{
    const USER_AUTHENTICATION_SUCCEED = 'user.authentication.succeed';

    /**
     * @var ServerRequestInterface
     */
    public $request;

    /**
     * @var ResponseInterface
     */
    public $response;

    /**
     * @var Token
     */
    private $_token;


    /**
     * @return Token
     */
    public function getToken()
    {
        if (
            !$this->_token instanceof Token
            && $this->response instanceof ResponseInterface
        ) {
            $response = Json::decode($this->response->getBody()->__toString());
            if (array_key_exists('access_token', $response)) {
                $this->_token = (new Parser())->parse($response['access_token']);
            }
        }

        return $this->_token;
    }
}
