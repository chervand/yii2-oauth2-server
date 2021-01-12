<?php

namespace chervand\yii2\oauth2\server\controllers;

use chervand\yii2\oauth2\server\components\Exception\OAuthHttpException;
use chervand\yii2\oauth2\server\models\AccessToken;
use chervand\yii2\oauth2\server\Module;
use League\OAuth2\Server\Exception\OAuthServerException;
use yii\helpers\Json;
use yii\rest\ActiveController;
use yii\rest\OptionsAction;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;

class TokenController extends ActiveController
{
    /**
     * @var string
     */
    public $modelClass = AccessToken::class;


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

    /**
     * @return mixed
     * @throws HttpException
     * @throws BadRequestHttpException
     * @throws OAuthHttpException
     */
    public function actionCreate()
    {
        /** @var Module $module */
        $module = $this->module;

        try {

            $response = $module->getAuthorizationServer()
                ->respondToAccessTokenRequest(
                    $module->getServerRequest(),
                    $module->getServerResponse()
                );

            return Json::decode($response->getBody()->__toString());

        } catch (OAuthServerException $exception) {

            throw new OAuthHttpException($exception);

        } catch (BadRequestHttpException $exception) {

            throw $exception;

        } catch (\Exception $exception) {

            throw new HttpException(
                500, 'Unable to respond to access token request.', 0,
                YII_DEBUG ? $exception : null
            );

        }
    }
}
