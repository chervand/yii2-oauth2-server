<?php
namespace chervand\yii2\oauth2\server\components\AuthMethods;

use chervand\yii2\oauth2\server\components\AuthorizationValidators\MacTokenValidator;
use chervand\yii2\oauth2\server\components\Exception\OAuthHttpException;
use chervand\yii2\oauth2\server\components\Psr7\ServerRequest;
use chervand\yii2\oauth2\server\components\Repositories\AccessTokenRepository;
use chervand\yii2\oauth2\server\components\ResourceServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use yii\web\HttpException;

class HttpMacAuth extends AuthMethod
{
    /**
     * @var CryptKey|string
     */
    public $publicKey;


    /**
     * @inheritdoc
     */
    public function authenticate($user, $request, $response)
    {
        $tokenRepository = new AccessTokenRepository();
        $tokenValidator = new MacTokenValidator($tokenRepository);

        $resourceServer = new ResourceServer(
            $tokenRepository,
            $this->publicKey,
            $tokenValidator
        );

        $serverRequest = new ServerRequest(\Yii::$app->request);

        try {
            return $resourceServer->validateAuthenticatedRequest($serverRequest);
        } catch (OAuthServerException $e) {
            throw new OAuthHttpException($e);
        } catch (\Exception $e) {
            throw new HttpException(500, 'Unable to validate the request.');
        }
    }

    /**
     * @inheritdoc
     */
    public function challenge($response)
    {
        $response->getHeaders()->set('WWW-Authenticate', 'MAC error="Invalid credentials"');
    }
}
