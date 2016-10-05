<?php
namespace chervand\yii2\oauth2\server;

use chervand\yii2\oauth2\server\components\AuthorizationServer;
use chervand\yii2\oauth2\server\components\Entities\ClientEntity;
use chervand\yii2\oauth2\server\components\Psr7\ServerRequest;
use chervand\yii2\oauth2\server\components\Psr7\ServerResponse;
use chervand\yii2\oauth2\server\components\Repositories\BearerTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\ClientRepository;
use chervand\yii2\oauth2\server\components\Repositories\MacTokenRepository;
use chervand\yii2\oauth2\server\components\ResponseTypes\BearerTokenResponse;
use chervand\yii2\oauth2\server\components\ResponseTypes\MacTokenResponse;
use chervand\yii2\oauth2\server\controllers\AuthorizeController;
use chervand\yii2\oauth2\server\controllers\TokenController;
use chervand\yii2\oauth2\server\models\Scope;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\GrantTypeInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
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
     * @var GrantTypeInterface[]
     */
    public $enabledGrantTypes = [];

    /**
     * @var AuthorizationServer
     */
    private $_authorizationServer;
    /**
     * @var ServerRequest
     */
    private $_serverRequest;
    /**
     * @var ServerResponse
     */
    private $_serverResponse;
    /**
     * @var AccessTokenRepositoryInterface
     */
    private $_accessTokenRepository;
    /**
     * @var ClientRepositoryInterface
     */
    private $_clientRepository;
    /**
     * @var ScopeRepositoryInterface
     */
    private $_scopeRepository;
    /**
     * @var ResponseTypeInterface
     */
    private $_responseType;


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
        $server = new AuthorizationServer(
            $this->getClientRepository(),
            $this->getAccessTokenRepository(),
            $this->getScopeRepository(),
            $this->privateKey,
            $this->publicKey,
            $this->getResponseType()
        );

        foreach ($this->enabledGrantTypes as $enabledGrantType) {
            if ($enabledGrantType instanceof GrantTypeInterface) {
                $server->enableGrantType(
                    $enabledGrantType,
                    new \DateInterval('PT1H') // access tokens will expire after 1 hour
                );
            }
        }

        return $server;
    }

    public function getClientRepository()
    {
        if (!isset($this->_clientRepository)) {
            $this->_clientRepository = new ClientRepository();
        }

        return $this->_clientRepository;
    }

    public function getAccessTokenRepository()
    {
        if (!$this->_accessTokenRepository instanceof AccessTokenRepositoryInterface) {
            $this->_accessTokenRepository = $this->prepareAccessTokenRepository();
        }

        return $this->_accessTokenRepository;
    }

    protected function prepareAccessTokenRepository()
    {
        switch (get_class($this->getResponseType())) {
            case MacTokenResponse::class:
                $class = MacTokenRepository::class;
                break;
            case BearerTokenResponse::class:
            default:
                $class = BearerTokenRepository::class;
        }

        return new $class(
            $this->privateKey,
            $this->publicKey
        );
    }

    public function getResponseType()
    {
        if (!$this->_responseType instanceof ResponseTypeInterface) {
            $this->_responseType = $this->prepareResponseType();
        }

        return $this->_responseType;
    }

    protected function prepareResponseType()
    {
        $client = $this->getClientEntity();

        if (
            $client instanceof ClientEntity
            && $client->token_type === ClientEntity::TOKEN_TYPE_MAC
        ) {
            return new MacTokenResponse();
        }

        return new BearerTokenResponse();
    }

    /**
     * @return ClientEntity
     */
    protected function getClientEntity()
    {
        return null;
    }

    public function getScopeRepository()
    {
        if (!$this->_scopeRepository instanceof ScopeRepositoryInterface) {
            $this->_scopeRepository = new Scope();
        }

        return $this->_scopeRepository;
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
            ->withParsedBody($request->bodyParams);
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
