<?php
namespace chervand\yii2\oauth2\server\models;

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
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $status
 */
class Client extends ActiveRecord
{
    const STATUS_DISABLED = -1;
    const STATUS_ACTIVE = 1;

    const GRANT_TYPE_AUTHORIZATION_CODE = 1;
    const GRANT_TYPE_IMPLICIT = 2;
    const GRANT_TYPE_PASSWORD = 3;
    const GRANT_TYPE_CLIENT_CREDENTIALS = 4;


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
}
