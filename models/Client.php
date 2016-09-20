<?php
namespace chervand\yii2\oauth2\server\models;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class ClientRelations
 * @package chervand\yii2\oauth2\server\models
 */
trait ClientRelations
{

}

/**
 * Class Client
 * @package chervand\yii2\oauth2\server\models
 *
 * @property integer $id
 */
class Client extends ActiveRecord implements ClientEntityInterface, ClientRepositoryInterface
{
    use ClientRelations;
    use EntityTrait;
    use ClientTrait;

    const STATUS_ACTIVE = 1;


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

    /**
     * @inheritdoc
     */
    public function getClientEntity($clientIdentifier, $grantType, $clientSecret = null, $mustValidateSecret = true)
    {
        // TODO[d6, 21/09/16]: inner join witch scopes
        // TODO[d6, 21/09/16]: If the client is confidential (i.e. is capable of securely storing a secret) and $mustValidateSecret === true then the secret must be validated.
        return static::find()
            ->andWhere(['identifier' => $clientIdentifier])
            ->active()->one();
    }
}

/**
 * Class ClientQuery
 * @package chervand\yii2\oauth2\server\models
 */
class ClientQuery extends ActiveQuery
{
    public function active()
    {
        return $this->andWhere(['status' => Client::STATUS_ACTIVE]);
    }
}
