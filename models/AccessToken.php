<?php

namespace chervand\yii2\oauth2\server\models;

use League\OAuth2\Server\CryptTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class AccessTokenRelations
 * @package chervand\yii2\oauth2\server\models
 *
 * @property Client $clientEntity
 */
trait AccessTokenRelations
{
    protected static $clientEntityClass = Client::class;


    public function getClientEntity()
    {
        /** @var AccessToken $this */
        return $this->hasOne(static::$clientEntityClass, ['id' => 'client_id'])/*->inverseOf('accessTokenEntity')*/
            ;
    }
}

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
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $status
 */
class AccessToken extends ActiveRecord
{
    use AccessTokenRelations;
    use CryptTrait;

    const TYPE_BEARER = 1;
    const TYPE_MAC = 2;

    const MAC_ALGORITHM_HMAC_SHA1 = 1;
    const MAC_ALGORITHM_HMAC_SHA256 = 2;

    const STATUS_ACTIVE = 1;
    const STATUS_REVOKED = -10;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auth__access_token}}';
    }

    /**
     * @inheritdoc
     * @return AccessTokenQuery
     */
    public static function find()
    {
        return new AccessTokenQuery(get_called_class());
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
            [['client_id'], 'required'],
            [['user_id'], 'default'],
            [['created_at', 'updated_at'], 'default', 'value' => time()],
            ['type', 'default', 'value' => static::TYPE_BEARER],
            ['type', 'in', 'range' => [static::TYPE_BEARER, static::TYPE_MAC]],
            ['mac_algorithm', 'default', 'value' => static::MAC_ALGORITHM_HMAC_SHA256],
            ['mac_algorithm', 'in', 'range' => array_keys(static::algorithms())],
            ['status', 'default', 'value' => static::STATUS_ACTIVE],
            ['status', 'in', 'range' => [static::STATUS_REVOKED, static::STATUS_ACTIVE]],
        ];
    }
}

/**
 * Class AccessTokenQuery
 * @package app\models
 */
class AccessTokenQuery extends ActiveQuery
{
    public function active()
    {
        return $this->andWhere(['status' => AccessToken::STATUS_ACTIVE]);
    }

    public function revoked()
    {
        return $this->andWhere(['status' => AccessToken::STATUS_REVOKED]);
    }

    public function expired()
    {
        return $this;
    }
}
