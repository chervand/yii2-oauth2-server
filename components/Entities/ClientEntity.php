<?php
namespace chervand\yii2\oauth2\server\components\Entities;

use chervand\yii2\oauth2\server\models\Client;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity extends Client implements ClientEntityInterface
{
    use ClientTrait;
    use EntityTrait;
}