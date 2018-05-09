<?php

namespace chervand\yii2\oauth2\server\components\Events;

use yii\base\Event;

class AuthorizationEvent extends Event
{
    const USER_AUTHENTICATION_SUCCEED = 'user.authentication.succeed';

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    public $request;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    public $response;
}
