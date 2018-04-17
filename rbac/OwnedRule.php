<?php

namespace chervand\yii2\oauth2\server\rbac;

use yii\rbac\Rule;

/**
 * Class OwnableRule implements RBAC rule which check whether
 * model passed to parameters is owned by a current user.
 *
 * @package app\components\rbac
 * @see OwnedInterface
 */
class OwnedRule extends Rule
{
    public $name = 'owned';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->createdAt = $this->updatedAt = time();
    }

    public function execute($user, $item, $params)
    {
        if (!isset($params['model']) || !isset($user)) {
            return false;
        }

        return
            $params['model'] instanceof OwnedInterface
            && $params['model']->getOwnerId() === $user;
    }
}
