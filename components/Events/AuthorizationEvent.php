<?php

namespace chervand\yii2\oauth2\server\components\Events;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use League\Event\Event;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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


    public function __construct($name, ServerRequestInterface $request, ResponseInterface $response)
    {
        parent::__construct($name);
        $this->request = $request;
        $this->response = $response;
    }

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
