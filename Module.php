<?php
namespace chervand\yii2\oauth2\server;

use chervand\yii2\oauth2\server\components\AuthorizationServer;
use chervand\yii2\oauth2\server\components\Psr7\ServerRequest;
use chervand\yii2\oauth2\server\components\Psr7\ServerResponse;
use chervand\yii2\oauth2\server\components\Repositories\AccessTokenRepository;
use chervand\yii2\oauth2\server\components\ResourceServer;
use chervand\yii2\oauth2\server\controllers\AuthorizeController;
use chervand\yii2\oauth2\server\controllers\TokenController;
use chervand\yii2\oauth2\server\models\Client;
use chervand\yii2\oauth2\server\models\Scope;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\GrantTypeInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use yii\base\BootstrapInterface;
use yii\helpers\ArrayHelper;
use yii\rest\UrlRule;
use yii\web\GroupUrlRule;

/**
 * Class Module
 * @package chervand\yii2\oauth2\server
 *
 * @property AuthorizationServer $authorizationServer
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * @var array
     */
    public $controllerMap = [
        'authorize' => AuthorizeController::class,
        'token' => TokenController::class,
    ];
    /**
     * @var array
     */
    public $urlManagerRules = [];
    /**
     * @var CryptKey|string
     */
    public $privateKey;
    /**
     * @var CryptKey|string
     */
    public $publicKey;
    /**
     * @var ResponseTypeInterface
     */
    public $responseType;
    /**
     * @var GrantTypeInterface[]
     */
    public $enabledGrantTypes = [];

    /**
     * @var AuthorizationServer
     */
    private $_authorizationServer;
    /**
     * @var ResourceServer
     */
    private $_resourceServer;
    /**
     * @var ServerRequest
     */
    private $_serverRequest;
    /**
     * @var ServerResponse
     */
    private $_serverResponse;


    /**
     * Sets module's URL manager rules on application's bootstrap.
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        $app->getUrlManager()
            ->addRules((new GroupUrlRule([
                'ruleConfig' => [
                    'class' => UrlRule::class,
                    'pluralize' => false,
                    'only' => ['create', 'options']
                ],
                'rules' => ArrayHelper::merge([
                    ['controller' => $this->uniqueId . '/authorize'],
                    ['controller' => $this->uniqueId . '/token']
                ], $this->urlManagerRules)
            ]))->rules, false);
    }

    public function init()
    {
        if (!$this->publicKey instanceof CryptKey) {
            $this->publicKey = new CryptKey($this->publicKey);
        }
        if (!$this->publicKey instanceof CryptKey) {
            $this->publicKey = new CryptKey($this->publicKey);
        }
    }

    /**
     * @return AuthorizationServer
     */
    public function getAuthorizationServer()
    {
        if (!$this->_authorizationServer instanceof AuthorizationServer) {
            $this->_authorizationServer = $this->prepareAuthorizationServer();
        }

        return $this->_authorizationServer;
    }

    /**
     * @return AuthorizationServer
     */
    protected function prepareAuthorizationServer()
    {
        $clientRepository = new Client();
        $accessTokenRepository = new AccessTokenRepository(
            $this->responseType,
            $this->privateKey,
            $this->publicKey
        );
        $scopeRepository = new Scope();

        $server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $this->privateKey,
            $this->publicKey,
            $this->responseType
        );

        foreach ($this->enabledGrantTypes as $enabledGrantType) {
            $server->enableGrantType(
                $enabledGrantType,
                new \DateInterval('PT1H') // access tokens will expire after 1 hour
            );
        }

        return $server;
    }

    /**
     * @return ResourceServer
     */
    public function getResourceServer()
    {
        if (!$this->_resourceServer instanceof ResourceServer) {
            $this->_resourceServer = $this->prepareResourceServer();
        }

        return $this->_resourceServer;
    }

    /**
     * @return ResourceServer
     */
    protected function prepareResourceServer()
    {
        return new ResourceServer(
            new AccessTokenRepository(),
            $this->publicKey
        );
    }

    /**
     * @return ServerRequest
     */
    public function getServerRequest()
    {
        if (!$this->_serverRequest instanceof ServerRequest) {
            $this->_serverRequest = $this->prepareServerRequest();
        }

        return $this->_serverRequest;
    }

    /**
     * @return ServerRequest
     */
    protected function prepareServerRequest()
    {
        $request = \Yii::$app->request;

        return (new ServerRequest($request))
            ->withParsedBody(ArrayHelper::merge([
                'client_id' => $request->getAuthUser(),
                'client_secret' => $request->getAuthPassword(),
            ], $request->bodyParams));
    }

    /**
     * @return ServerResponse
     */
    public function getServerResponse()
    {
        if (!$this->_serverResponse instanceof ServerResponse) {
            $this->_serverResponse = $this->prepareServerResponse();
        }

        return $this->_serverResponse;
    }

    /**
     * @return ServerResponse
     */
    protected function prepareServerResponse()
    {
        return new ServerResponse();
    }
}
