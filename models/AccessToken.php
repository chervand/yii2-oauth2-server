<?php

namespace chervand\yii2\oauth2\server\models;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\CryptTrait;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use yii\db\ActiveRecord;
use yii\filters\RateLimitInterface;
use yii\helpers\ArrayHelper;

/**
 * Class AccessToken
 * @package chervand\yii2\oauth2\server\models
 *
 * @property integer $id
 * @property integer $client_id
 * @property integer $user_id
 * @property string $identifier
 * @property string $mac_key
 * @property string $mac_algorithm
 * @property integer $type
 * @property integer $allowance
 * @property integer $allowance_updated_at
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $expired_at
 * @property integer $status
 *
 * @property Client $relatedClient
 * @property Scope[] $grantedScopes
 *
 * @todo save transaction
 */
class AccessToken extends ActiveRecord implements AccessTokenEntityInterface, RateLimitInterface
{
    use CryptTrait, EntityTrait;
    use AccessTokenTrait, TokenEntityTrait; // todo: get rid of this

    const TYPE_BEARER = 1;
    const TYPE_MAC = 2;

    const MAC_ALGORITHM_HMAC_SHA1 = 1;
    const MAC_ALGORITHM_HMAC_SHA256 = 2;

    const STATUS_ACTIVE = 1;
    const STATUS_REVOKED = -10;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%auth__access_token}}';
    }

    /**
     * {@inheritdoc}
     * @return AccessTokenQuery
     */
    public static function find()
    {
        return new AccessTokenQuery(get_called_class());
    }

    public function getRelatedClient()
    {
        return $this->hasOne(Client::class, ['id' => 'client_id'])/* todo: ->inverseOf('accessTokens') */;
    }

    public function getMacAlgorithm()
    {
        return ArrayHelper::getValue(
            static::algorithms(),
            $this->mac_algorithm,
            'hmac-sha-256'
        );
    }

    public static function algorithms()
    {
        return [
            static::MAC_ALGORITHM_HMAC_SHA1 => 'hmac-sha-1',
            static::MAC_ALGORITHM_HMAC_SHA256 => 'hmac-sha-256',
        ];
    }

    public function rules()
    {
        return [
            [['client_id'], 'required'], // identifier
            [['user_id'], 'default'],
            ['type', 'default', 'value' => static::TYPE_BEARER],
            ['type', 'in', 'range' => [static::TYPE_BEARER, static::TYPE_MAC]],
            ['mac_algorithm', 'default', 'value' => static::MAC_ALGORITHM_HMAC_SHA256],
            ['mac_algorithm', 'in', 'range' => array_keys(static::algorithms())],
            [['!allowance'], 'default'],
            [['!allowance_updated_at', '!created_at', '!updated_at'], 'default', 'value' => time()],
            ['status', 'default', 'value' => static::STATUS_ACTIVE],
            ['status', 'in', 'range' => [static::STATUS_REVOKED, static::STATUS_ACTIVE]],
        ];
    }

    public function getGrantedScopes()
    {
        return $this->hasMany(Scope::class, ['id' => 'scope_id'])
            ->viaTable('{{auth__access_token_scope}}', ['access_token_id' => 'id']);
    }

    /**
     * {@inheritDoc}
     */
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

        $builder = $this->finalizeJWTBuilder($builder);

        return $builder
            ->sign(new Sha256(), new Key($privateKey->getKeyPath(), $privateKey->getPassPhrase()))
            ->getToken();
    }

    /**
     * Override it in order to set additional public or private claims.
     *
     * @param Builder $builder
     * @return Builder
     * @see https://tools.ietf.org/html/rfc7519#section-4
     */
    protected function finalizeJWTBuilder(Builder $builder)
    {
        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function getClient()
    {
        return $this->relatedClient;
    }

    public function getScopes()
    {
        if (empty($this->scopes)) {
            $this->scopes = $this->grantedScopes;
        }

        return array_values($this->scopes);
    }

    /**
     * {@inheritdoc}
     */
    public function setUserIdentifier($identifier)
    {
        $this->user_id = $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserIdentifier()
    {
        return $this->user_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRateLimit($request, $action)
    {
        return [1000, 600];
    }

    /**
     * {@inheritdoc}
     */
    public function loadAllowance($request, $action)
    {
        return [
            $this->allowance === null ? 1000 : $this->allowance,
            $this->allowance_updated_at
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function saveAllowance($request, $action, $allowance, $timestamp)
    {
        $this->updateAttributes([
            'allowance' => $allowance,
            'allowance_updated_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
