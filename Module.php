<?php

namespace chervand\yii2\oauth2\server;

use chervand\yii2\oauth2\server\components\Psr7\ServerRequest;
use chervand\yii2\oauth2\server\components\Psr7\ServerResponse;
use chervand\yii2\oauth2\server\components\Repositories\BearerTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\ClientRepository;
use chervand\yii2\oauth2\server\components\Repositories\MacTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\RefreshTokenRepository;
use chervand\yii2\oauth2\server\components\Repositories\RepositoryCacheInterface;
use chervand\yii2\oauth2\server\components\Repositories\ScopeRepository;
use chervand\yii2\oauth2\server\components\ResponseTypes\MacTokenResponse;
use chervand\yii2\oauth2\server\components\Server\AuthorizationServer;
use chervand\yii2\oauth2\server\controllers\AuthorizeController;
use chervand\yii2\oauth2\server\controllers\RevokeController;
use chervand\yii2\oauth2\server\controllers\TokenController;
use chervand\yii2\oauth2\server\models\Client;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use yii\rest\UrlRule;
use yii\web\GroupUrlRule;

/**
 * Class Module
 * @package chervand\yii2\oauth2\server
 *
 * @property-read AuthorizationServer $authorizationServer
 * @property-read AccessTokenRepositoryInterface $accessTokenRepository
 * @property ClientRepositoryInterface $clientRepository
 * @property RefreshTokenRepositoryInterface $refreshTokenRepository
 * @property ScopeRepositoryInterface $scopeRepository
 * @property UserRepositoryInterface $userRepository
 * @property ResponseTypeInterface $responseType
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
        'revoke' => [
            'class' => RevokeController::class,
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
     * @var callable
     */
    public $enableGrantTypes;

    /**
     * @var array
     */
    public $cache;

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
     * @var ResponseTypeInterface
     */
    private $_responseType;


    /**
     * Sets module's URL manager rules on application's bootstrap.
     * @param Application $app
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
                    ['controller' => $this->uniqueId . '/revoke'],
                    ['controller' => $this->uniqueId . '/token'],
                ], $this->urlManagerRules)
            ]))->rules, false);
    }

    public function __construct($id, $parent = null, $config = [])
    {
        parent::__construct($id, $parent, ArrayHelper::merge([
            'components' => [
                'userRepository' => [
                    'class' => Yii::$app->user->identityClass,
                ],
                'clientRepository' => [
                    'class' => ClientRepository::class,
                ],
                'scopeRepository' => [
                    'class' => ScopeRepository::class,
                ],
                'refreshTokenRepository' => [
                    'class' => RefreshTokenRepository::class,
                ],
            ],
        ], $config));
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (!$this->privateKey instanceof CryptKey) {
            $this->privateKey = new CryptKey($this->privateKey);
        }
        if (!$this->publicKey instanceof CryptKey) {
            $this->publicKey = new CryptKey($this->publicKey);
        }
    }

    /**
     * @return AuthorizationServer
     * @throws OAuthServerException
     */
    public function getAuthorizationServer()
    {
        if (!$this->_authorizationServer instanceof AuthorizationServer) {
            $this->prepareAuthorizationServer();
        }

        return $this->_authorizationServer;
    }

    /**
     * @throws OAuthServerException
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

    /**
     * @return BearerTokenRepository|MacTokenRepository|AccessTokenRepositoryInterface
     * @throws InvalidConfigException
     */
    public function getAccessTokenRepository()
    {
        if (!$this->_accessTokenRepository instanceof AccessTokenRepositoryInterface) {
            $this->_accessTokenRepository = $this->prepareAccessTokenRepository();
        }

        if ($this->_accessTokenRepository instanceof RepositoryCacheInterface) {
            $this->_accessTokenRepository->setCache(
                ArrayHelper::getValue($this->cache, AccessTokenRepositoryInterface::class)
            );
        }

        return $this->_accessTokenRepository;
    }

    /**
     * @return BearerTokenRepository|MacTokenRepository
     * @throws InvalidConfigException
     */
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
            $request = Yii::$app->request;
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

    /**
     * @param ClientEntityInterface $clientEntity
     */
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
            $request = Yii::$app->request;
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
