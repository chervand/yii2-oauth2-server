<?php
namespace chervand\yii2\oauth2\server\models;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Class ScopeRelations
 * @package \models
 */
trait ScopeRelations
{

}

/**
 * Class Scope
 * @package \models
 *
 * @property integer $id
 * @property string $identifier
 */
class Scope extends ActiveRecord implements ScopeEntityInterface, ScopeRepositoryInterface
{
    use ScopeRelations;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auth__scope}}';
    }

    /**
     * @inheritdoc
     * @return ScopeQuery
     */
    public static function find()
    {
        return new ScopeQuery(get_called_class());
    }

    /**
     * Get the scope's identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        // TODO: Implement getIdentifier() method.
    }

    /**
     * Return information about a scope.
     *
     * @param string $identifier The scope identifier
     *
     * @return ScopeEntityInterface
     */
    public function getScopeEntityByIdentifier($identifier)
    {
        // TODO: Implement getScopeEntityByIdentifier() method.
        return null;
    }

    /**
     * Given a client, grant type and optional user identifier validate the set of scopes requested are valid and optionally
     * append additional scopes or remove requested scopes.
     *
     * @param ScopeEntityInterface[] $scopes
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     * @param null|string $userIdentifier
     *
     * @return ScopeEntityInterface[]
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    )
    {
        // TODO: Implement finalizeScopes() method.
        return [];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }
}

/**
 * Class ScopeQuery
 * @package \models
 */
class ScopeQuery extends ActiveQuery
{

}
