<?php
namespace chervand\yii2\oauth2\server\components\Entities;

use chervand\yii2\oauth2\server\models\AccessToken;
use chervand\yii2\oauth2\server\models\Client;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity extends AccessToken implements AccessTokenEntityInterface
{
    use AccessTokenTrait;
    use TokenEntityTrait;


    public function convertToJWT(CryptKey $privateKey)
    {
        $builder = (new Builder())
            ->setAudience($this->getClient()->getIdentifier())
            ->setId($this->getIdentifier(), true)
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration($this->getExpiryDateTime()->getTimestamp())
            ->setSubject($this->getUserIdentifier())
            ->set('scopes', $this->getScopes());

        if ($this->type == static::TYPE_MAC) {
            $builder
                ->setHeader('kid', $this->identifier)
                ->set('kid', $this->identifier)
                ->set('mac_key', $this->mac_key);
        }

        return $builder
            ->sign(new Sha256(), new Key($privateKey->getKeyPath(), $privateKey->getPassPhrase()))
            ->getToken();
    }

    public function getClient()
    {
        return new Client();
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getScopes()
    {
        if (empty($this->scopes)) {
            $this->scopes = $this->grantedScopes;
        }

        return array_values($this->scopes);
    }


}
