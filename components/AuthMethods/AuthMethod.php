<?php
namespace chervand\yii2\oauth2\server\components\AuthMethods;

use chervand\yii2\oauth2\server\components\Exception\OAuthHttpException;
use chervand\yii2\oauth2\server\components\Psr7\ServerRequest;
use chervand\yii2\oauth2\server\components\ResourceServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use yii\web\HttpException;
use yii\web\Request;

abstract class AuthMethod extends \yii\filters\auth\AuthMethod
{
    /**
     * @var CryptKey|string
     */
    public $publicKey;

    public function authenticate($user, $request, $response)
    {
        if (!$this->tokenTypeExists($request)) {
            return null;
        }

        return $this->validate(new ServerRequest($request));
    }

    protected function tokenTypeExists(Request &$request)
    {
        $authHeader = $request->getHeaders()->get('Authorization');

        if (
            $authHeader !== null && $this->getTokenType() !== null
            && preg_match('/^' . $this->getTokenType() . '\s+(.*?)$/', $authHeader, $matches)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    protected abstract function getTokenType();

    protected function validate(ServerRequestInterface $serverRequest)
    {
        $resourceServer = new ResourceServer(
            $this->getAccessTokenRepository(),
            $this->publicKey,
            $this->getAuthorizationValidator()
        );

        try {
            return $resourceServer->validateAuthenticatedRequest($serverRequest);
        } catch (OAuthServerException $e) {
            throw new OAuthHttpException($e);
        } catch (\Exception $e) {
            throw new HttpException(500, 'Unable to validate the request.', 0, YII_DEBUG ? $e : null);
        }
    }

    /**
     * @return AccessTokenRepositoryInterface
     */
    protected abstract function getAccessTokenRepository();

    /**
     * @return \League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface
     */
    protected abstract function getAuthorizationValidator();
}
