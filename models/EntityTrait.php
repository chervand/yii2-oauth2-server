<?php

namespace chervand\yii2\oauth2\server\models;

use yii\db\ActiveRecordInterface;

trait EntityTrait
{
    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        /** @var ActiveRecordInterface $this */
        return $this->getAttribute('identifier');
    }

    /**
     * @param mixed $identifier
     */
    public function setIdentifier($identifier)
    {
        /** @var ActiveRecordInterface $this */
        $this->setAttribute('identifier', $identifier);
    }
}
