<?php

namespace chervand\yii2\oauth2\server\rbac;

use yii\base\Configurable;
use yii\rbac\ManagerInterface;

/**
 * Class ScopePermission
 * @package chervand\yii2\oauth2\server\rbac
 */
class ScopePermission extends Permission implements Configurable
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        /** @var ManagerInterface $authManager */
        $authManager = \Yii::$app->authManager;

        $grantedRule = new GrantedRule();
        $authManager->add($grantedRule);
        $this->ruleName = $grantedRule->name;

        parent::init();
    }
}
