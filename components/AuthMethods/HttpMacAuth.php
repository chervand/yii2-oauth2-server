<?php
namespace chervand\yii2\oauth2\server\components\AuthMethods;

use chervand\yii2\oauth2\server\components\AuthorizationValidators\MacTokenValidator;
use chervand\yii2\oauth2\server\components\Repositories\MacTokenRepository;
use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class HttpMacAuth extends AuthMethod
{
    private $_authorizationValidator;
    private $_accessTokenRepository;

    /**
     * {@inheritdoc}
     */
    public function challenge($response)
    {
        $response->getHeaders()->set('WWW-Authenticate', 'MAC error="Invalid credentials"');
    }

    protected function getTokenType()
    {
        return 'MAC';
    }

    /**
     * @return AuthorizationValidatorInterface
     * @throws \yii\base\InvalidConfigException
     */
    protected function getAuthorizationValidator()
    {
        if (!$this->_authorizationValidator instanceof AuthorizationValidatorInterface) {
            $this->_authorizationValidator = new MacTokenValidator($this->getAccessTokenRepository());
        }

        return $this->_authorizationValidator;
    }

    /**
     * @return AccessTokenRepositoryInterface
     * @throws \yii\base\InvalidConfigException
     */
    protected function getAccessTokenRepository()
    {
        if (!$this->_accessTokenRepository instanceof AccessTokenRepositoryInterface) {
            $this->_accessTokenRepository = new MacTokenRepository();
        }

        return $this->_accessTokenRepository;
    }
}
