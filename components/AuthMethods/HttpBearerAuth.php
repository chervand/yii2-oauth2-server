<?php
namespace chervand\yii2\oauth2\server\components\AuthMethod;

use chervand\yii2\oauth2\server\components\Exception\OAuthHttpException;
use chervand\yii2\oauth2\server\Module;
use League\OAuth2\Server\Exception\OAuthServerException;
use yii\web\HttpException;

class HttpBearerAuth extends \yii\filters\auth\HttpBearerAuth
{
    /**
     * @var Module
     */
    public $module;


    /**
     * @inheritdoc
     */
    public function authenticate($user, $request, $response)
    {
        try {
            $serverRequest = $this->module->getServerRequest();
            return $this->module->getResourceServer()
                ->validateAuthenticatedRequest($serverRequest);
        } catch (OAuthServerException $e) {
            throw new OAuthHttpException($e);
        } catch (\Exception $e) {
            throw new HttpException(500, 'Unable to validate the request.');
        }
    }
}
