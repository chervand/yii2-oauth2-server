<?php

namespace chervand\yii2\oauth2\server;

use chervand\yii2\oauth2\server\components\Psr7\ServerRequest;
use chervand\yii2\oauth2\server\components\Psr7\ServerResponse;
use chervand\yii2\oauth2\server\components\Repositories\BearerTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\ClientRepository;
use chervand\yii2\oauth2\server\components\Repositories\MacTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\RefreshTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\ScopeRepository;
use chervand\yii2\oauth2\server\components\ResponseTypes\MacTokenResponse;
use chervand\yii2\oauth2\server\components\Server\AuthorizationServer;
use chervand\yii2\oauth2\server\controllers\AuthorizeController;
use chervand\yii2\oauth2\server\controllers\TokenController;
use chervand\yii2\oauth2\server\models\Client;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use yii\base\BootstrapInterface;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use yii\rest\UrlRule;
use yii\web\GroupUrlRule;

/**
 * Class Module
 * @package chervand\yii2\oauth2\server
 *
 * @property-read AuthorizationServer $authorizationServer
 * @property-read \League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface $accessTokenRepository
 * @property \League\OAuth2\Server\Repositories\ClientRepositoryInterface $clientRepository
 * @property \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface $refreshTokenRepository
 * @property \League\OAuth2\Server\Repositories\ScopeRepositoryInterface $scopeRepository
 * @property \League\OAuth2\Server\Repositories\UserRepositoryInterface $userRepository
 * @property \League\OAuth2\Server\ResponseTypes\ResponseTypeInterface $responseType
 *
 * @todo: ability to define access token type for refresh token grant, client-refresh grant type connection review
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * @var array
     */
    public $controllerMap = [
        'authorize' => [
            'class' => AuthorizeController::class,
            'as corsFilter' => Cors::class,
        ],
        'token' => [
            'class' => TokenController::class,
            'as corsFilter' => Cors::class,
        ],
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
     * @var \League\OAuth2\Server\ResponseTypes\ResponseTypeInterface
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
        $this->_responseType = ArrayHelper::getValue($this, 'clientEntity.responseType');

        $this->_authorizationServer = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $this->privateKey,
            $this->_encryptionKey,
            $this->_responseType
        );

        if (is_callable($this->enableGrantTypes) !== true) {
            $this->enableGrantTypes = function (Module &$module) {
                throw OAuthServerException::unsupportedGrantType();
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
        if ($this->_responseType instanceof MacTokenResponse) {
            return new MacTokenRepository($this->_encryptionKey);
        }

        return new BearerTokenRepository();
    }

    /**
     * @return Client
     * @throws OAuthServerException
     */
    protected function getClientEntity()
    {
        if (!$this->_clientEntity instanceof ClientEntityInterface) {
            $request = \Yii::$app->request;
            $this->_clientEntity = $this->clientRepository
                ->getClientEntity(
                    $request->getAuthUser(),
                    null, // fixme: need to provide grant type
                    $request->getAuthPassword()
                );
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
