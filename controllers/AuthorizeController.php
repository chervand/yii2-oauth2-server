<?php

namespace chervand\yii2\oauth2\server\controllers;

use yii\rest\ActiveController;
use yii\rest\OptionsAction;

class AuthorizeController extends ActiveController
{
    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'options' => [
                'class' => OptionsAction::class,
            ],
        ];
    }
}
