<?php
namespace chervand\yii2\oauth2\server\models;

use chervand\yii2\oauth2\server\components\ResponseTypes\BearerTokenResponse;
use chervand\yii2\oauth2\server\components\ResponseTypes\MacTokenResponse;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Client
 * @package chervand\yii2\oauth2\server\models
 *
 * @property integer $id
 * @property string $identifier
 * @property string $secret
 * @property string $name
 * @property string $redirect_uri
 * @property integer $token_type
 * @property integer $grant_type
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $status
 *
 * @property Scope[] $relatedScopes
 * @property Scope[] $relatedScopesDefault
 */
class Client extends ActiveRecord implements ClientEntityInterface
{
    const STATUS_DISABLED = -1;
    const STATUS_ACTIVE = 1;

    const TOKEN_TYPE_BEARER = AccessToken::TYPE_BEARER;
    const TOKEN_TYPE_MAC = AccessToken::TYPE_MAC;

    const GRANT_TYPE_AUTHORIZATION_CODE = 1;
    const GRANT_TYPE_IMPLICIT = 2;
    const GRANT_TYPE_PASSWORD = 3;
    const GRANT_TYPE_CLIENT_CREDENTIALS = 4;
    const GRANT_TYPE_REFRESH_TOKEN = 5;
    const GRANT_TYPE_REVOKE = 6;

    /**
     * @var ResponseTypeInterface
     */
    private $_responseType;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auth__client}}';
    }

    /**
     * @inheritdoc
     * @return ClientQuery
     */
    public static function find()
    {
        return new ClientQuery(get_called_class());
    }

    public static function grants()
    {
        return [
            static::GRANT_TYPE_AUTHORIZATION_CODE => 'authorization_code',
            static::GRANT_TYPE_IMPLICIT => 'implicit',
            static::GRANT_TYPE_PASSWORD => 'password',
            static::GRANT_TYPE_CLIENT_CREDENTIALS => 'client_credentials',
            static::GRANT_TYPE_REFRESH_TOKEN => 'refresh_token',
            static::GRANT_TYPE_REVOKE => 'revoke',
        ];
    }

    public static function getGrantTypeId($grantType, $default = null)
    {
        return ArrayHelper::getValue(array_flip(static::grants()), $grantType, $default);
    }

    public static function secretHash($secret)
    {
        return password_hash($secret, PASSWORD_DEFAULT);
    }

    public static function secretVerify($secret, $hash)
    {
        return password_verify($secret, $hash);
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }

    public function getResponseType()
    {
        if (!$this->_responseType instanceof ResponseTypeInterface) {

            if (
                isset($this->token_type)
                && $this->token_type === static::TOKEN_TYPE_MAC
            ) {
                $this->_responseType = new MacTokenResponse();
            } else {
                $this->_responseType = new BearerTokenResponse();
            }

        }

        return $this->_responseType;
    }

    /**
     * @param callable|null $callable
     * @return ClientQuery|ActiveQuery
     */
    public function getRelatedScopes(callable $callable = null)
    {
        return $this->hasMany(Scope::class, ['id' => 'scope_id'])
            ->viaTable('{{auth__client_scope}}', ['client_id' => 'id'], $callable);
    }

    public function getIsConfidential()
    {
        return $this->secret !== null;
    }
}
