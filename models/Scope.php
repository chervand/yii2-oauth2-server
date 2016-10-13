<?php
namespace chervand\yii2\oauth2\server\models;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use yii\db\ActiveRecord;

/**
 * Class Scope
 * @package \models
 *
 * @property integer $id
 * @property string $identifier
 */
class Scope extends ActiveRecord implements ScopeEntityInterface
{
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
        return $this->identifier;
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
        return $this->getIdentifier();
    }
}
