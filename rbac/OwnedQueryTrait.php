<?php

namespace chervand\yii2\oauth2\server\rbac;

use yii\db\ActiveQuery;

trait OwnedQueryTrait
{
    /**
     * @param mixed $attribute user's id attribute or alias name
     * @param mixed $value user's id value, defaults current user's identity id
     * @param bool $strict if false empty value will be ignored, defaults to true
     * @return $this
     */
    public function owned($attribute, $value = null, $strict = true)
    {
        $user = \Yii::$app->user;

        if ($value === null) {
            $value = $user->getId();
        }

        /** @var ActiveQuery $this */
        if ($strict === true) {
            return $this->andWhere([$attribute => $value]);
        } else {
            return $this->andFilterWhere([$attribute => $value]);
        }
    }
}
