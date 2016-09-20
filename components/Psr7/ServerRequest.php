<?php
namespace chervand\yii2\oauth2\server\components\Psr7;

use yii\web\Request;

/**
 * Class ServerRequest
 * @package chervand\yii2\oauth2\server\components\Psr7
 *
 * @link https://github.com/yiisoft/yii2/issues/11328
 */
class ServerRequest extends \GuzzleHttp\Psr7\ServerRequest
{
    /**
     * ServerRequest constructor.
     * @param Request $request
     */
    public function __construct(Request &$request)
    {
        parent::__construct(
            $request->method,
            $request->url,
            $request->headers->toArray(),
            $request->rawBody
        );
    }
}
