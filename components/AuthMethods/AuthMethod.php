<?php
namespace chervand\yii2\oauth2\server\components\AuthMethods;

use chervand\yii2\oauth2\server\components\Exception\OAuthHttpException;
use chervand\yii2\oauth2\server\components\Psr7\ServerRequest;
use chervand\yii2\oauth2\server\components\ResourceServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use yii\web\HttpException;
use yii\web\Request;
use yii\web\Response;
use yii\web\User;

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

        return $this->validate(
            new ResourceServer(
                $this->getAccessTokenRepository(),
                $this->publicKey,
                $this->getAuthorizationValidator()
            ),
            new ServerRequest(
                $this->request ?: \Yii::$app->getRequest()
            ),
            $this->response ?: \Yii::$app->getResponse(),
            $this->user ?: \Yii::$app->getUser()
        );
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

    protected function validate(
        ResourceServer $resourceServer,
        ServerRequest $serverRequest,
        Response $response,
        User $user
    )
    {
        try {

            $serverRequest = $resourceServer
                ->validateAuthenticatedRequest($serverRequest);

            $identity = $user->loginByAccessToken(
                $serverRequest->getAttribute('oauth_access_token_id'),
                get_called_class()
            );

            if (
                $identity === null
                || $serverRequest->getAttribute('oauth_user_id') != $identity->getId()
            ) {
                $this->handleFailure($response);
            }

            return $identity;

        } catch (OAuthServerException $e) {
            throw new OAuthHttpException($e);
        } catch (\Exception $e) {
            throw new HttpException(500, 'Unable to validate the request.', 0, YII_DEBUG ? $e : null);
        }
    }

    public function handleFailure($response)
    {
        throw OAuthServerException::accessDenied();
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
