<?php

namespace chervand\yii2\oauth2\server\components\ResponseTypes;

use League\OAuth2\Server\ResponseTypes\AbstractResponseType;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RevokeResponse
 * @package chervand\yii2\oauth2\server\components
 * @link https://tools.ietf.org/html/rfc7009
 */
class RevokeResponse extends AbstractResponseType
{
    /**
     * {@inheritdoc}
     */
    public function generateHttpResponse(ResponseInterface $response)
    {
        $response = $response
            ->withStatus(200)
            ->withHeader('pragma', 'no-cache')
            ->withHeader('cache-control', 'no-store');

        return $response;
    }
}
