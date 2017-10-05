<?php

namespace chervand\yii2\oauth2\server;

use chervand\yii2\oauth2\server\components\Psr7\ServerRequest;
use chervand\yii2\oauth2\server\components\Psr7\ServerResponse;
use chervand\yii2\oauth2\server\components\Repositories\BearerTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\ClientRepository;
use chervand\yii2\oauth2\server\components\Repositories\MacTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\RefreshTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\ScopeRepository;
use chervand\yii2\oauth2\server\components\ResponseTypes\BearerTokenResponse;
use chervand\yii2\oauth2\server\components\ResponseTypes\MacTokenResponse;
use chervand\yii2\oauth2\server\controllers\AuthorizeController;
use chervand\yii2\oauth2\server\controllers\TokenController;
use chervand\yii2\oauth2\server\models\Client;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use yii\base\BootstrapInterface;
use yii\helpers\ArrayHelper;
use yii\rest\UrlRule;
use yii\web\GroupUrlRule;

/**
 * Class Module
 * @package chervand\yii2\oauth2\server
 *
 * @property-read \League\OAuth2\Server\AuthorizationServer $authorizationServer
 * @property-read \League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface $accessTokenRepository
 * @property \League\OAuth2\Server\Repositories\ClientRepositoryInterface $clientRepository
 * @property \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface $refreshTokenRepository
 * @property \League\OAuth2\Server\Repositories\ScopeRepositoryInterface $scopeRepository
 * @property \League\OAuth2\Server\Repositories\UserRepositoryInterface $userRepository
 *
 * @todo: ability to define access token type for refresh token grant, client-refresh grant type connection review
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
     * @var callable todo: doc
     */
    public $enableGrantTypes;

    /**
     * @var AuthorizationServer
     */
    private $_authorizationServer;
    /**
     * @var string
     */
    private $_encryptionKey;

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
     * @var ClientEntityInterface|Client
     */
    private $_clientEntity;


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

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        \Yii::configure($this, [
            'components' => ArrayHelper::merge([
                'clientRepository' => ClientRepository::class,
                'refreshTokenRepository' => RefreshTokenRepository::class,
                'scopeRepository' => ScopeRepository::class,
                'userRepository' => \Yii::$app->user->identityClass,
            ], $this->components)
        ]);

        if (!$this->privateKey instanceof CryptKey) {
            $this->privateKey = new CryptKey($this->privateKey);
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
            $this->prepareAuthorizationServer();
        }

        return $this->_authorizationServer;
    }

    /**
     */
    protected function prepareAuthorizationServer()
    {
        $this->_authorizationServer = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $this->privateKey,
            $this->_encryptionKey,
            $this->getClientEntity()->getResponseType()
        );

        if (is_callable($this->enableGrantTypes) !== true) {
            $this->enableGrantTypes = function (Module &$module) {
                // todo: enable auth code grant by default when implemented
            };
        }

        call_user_func_array($this->enableGrantTypes, [&$this]);
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
        switch (get_class($this->getClientEntity()->getResponseType())) {
            case MacTokenResponse::class:
                $tokenRepository = new MacTokenRepository($this->_encryptionKey);
                break;
            case BearerTokenResponse::class:
            default:
                $tokenRepository = new BearerTokenRepository();
        }

        return $tokenRepository;
    }

    /**
     * @return Client
     * @throws OAuthServerException
     */
    protected function getClientEntity()
    {
        // todo: fix this
        if (!$this->_clientEntity instanceof ClientEntityInterface) {
            $this->_clientEntity = $this->clientRepository
                ->getClientEntity('client1', null, null, false, false);
        }

        if ($this->_clientEntity instanceof ClientEntityInterface) {
            return $this->_clientEntity;
        }

        throw OAuthServerException::invalidClient();
    }

    public function setClientEntity(ClientEntityInterface $clientEntity)
    {
        $this->_clientEntity = $clientEntity;
    }

    /**
     * @return ServerRequest
     */
    public function getServerRequest()
    {
        if (!$this->_serverRequest instanceof ServerRequest) {
            $request = \Yii::$app->request;
            $this->_serverRequest = (new ServerRequest($request))
                ->withParsedBody($request->bodyParams);
        }

        return $this->_serverRequest;
    }

    /**
     * @return ServerResponse
     */
    public function getServerResponse()
    {
        if (!$this->_serverResponse instanceof ServerResponse) {
            $this->_serverResponse = new ServerResponse();
        }

        return $this->_serverResponse;
    }

    /**
     * @param string $encryptionKey
     */
    public function setEncryptionKey($encryptionKey)
    {
        $this->_encryptionKey = $encryptionKey;
    }
}
