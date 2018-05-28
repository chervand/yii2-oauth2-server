# Yii 2.0 OAuth 2.0 Server

`chervand/yii2-oauth2-server` is a `Yii 2.0 PHP Framework` integration of [`thephpleague/oauth2-server`](https://github.com/thephpleague/oauth2-server) library which implements a standards compliant [`OAuth 2.0 Server`](https://tools.ietf.org/html/rfc6749) for PHP. It supports all of the grants defined in the specification with usage of `JWT` `Bearer` tokens.

`chervand/yii2-oauth2-server` additionally provides [`MAC`](https://tools.ietf.org/html/draft-ietf-oauth-v2-http-mac-05) tokens support, which is not supported by the original library, because `MAC` tokens specification is currently in draft and it was not updated since 2014, so it's a pretty experimental feature.
 
 It also includes tokens revocation implementation based on [RFC7009](https://tools.ietf.org/html/rfc7009).

## Installation

### Applying DB migrations

    ./yii migrate --migrationPath="@vendor/chervand/yii2-oauth2-server/migrations"

### Generating public and private keys

See [OAuth 2.0 Server installation](https://oauth2.thephpleague.com/installation/) page.

### Integrating with your users

To integrate OAuth 2.0 server with your users DB, you should implement `League\OAuth2\Server\Repositories\UserRepositoryInterface` for a `user` component's `identityClass` which should be extended from `chervand\yii2\oauth2\server\models\AccessToken`. `League\OAuth2\Server\Repositories\UserRepositoryInterface::getUserEntityByUserCredentials()` should return your user model instance implementing `League\OAuth2\Server\Entities\UserEntityInterface` or `null`. You may additionally add a foreign key for the `auth__access_token.user_id` column referencing your users table. You mau also override `getRateLimit()` to provider ` yii\filters\RateLimitInterface` with required values.

```php
<?php 
/** 
 * config/main.php 
 */
return [
    // ...
    'components' => [
        // ...
        'user' => [
            'identityClass' => 'app\components\Identity',
            // ...
        ],
        // ...
    ],
    // ...
];
```

### Configuring the authorization server

Module configuration:

```php
<?php 
/** 
 * config/main.php 
 */
return [
    // ...
    'bootstrap' => [
        'oauth2',
        // ...
    ],
    'modules' => [
        'oauth2' => [
            'class' => \chervand\yii2\oauth2\server\Module::class,
            'privateKey' => __DIR__ . '/../private.key',
            'publicKey' => __DIR__ . '/../public.key',
            'cache' => [
                \League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface::class => [
                    'cacheDuration' => 3600,
                    'cacheDependency' => new \yii\caching\FileDependency(['fileName' => 'example.txt']),
                ],
                \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface::class => [
                    'cacheDuration' => 3600,
                    'cacheDependency' => new \yii\caching\FileDependency(['fileName' => 'example.txt']),
                ],
            ],
            'enableGrantTypes' => function (\chervand\yii2\oauth2\server\Module &$module) {
                $server = $module->authorizationServer;
                $server->enableGrantType(new \League\OAuth2\Server\Grant\ImplicitGrant(
                    new \DateInterval('PT1H')
                ));
                $server->enableGrantType(new \League\OAuth2\Server\Grant\PasswordGrant(
                    $module->userRepository,
                    $module->refreshTokenRepository
                ));
                $server->enableGrantType(new \League\OAuth2\Server\Grant\ClientCredentialsGrant());
                $server->enableGrantType(new \League\OAuth2\Server\Grant\RefreshTokenGrant(
                    $module->refreshTokenRepository
                ));
                $server->enableGrantType(new \chervand\yii2\oauth2\server\components\Grant\RevokeGrant(
                    $module->refreshTokenRepository,
                    $module->publicKey
                ));
            },
        ],
        // ...
    ],
    // ...
];
```

### Configuring the resource server

Controller's behaviors configuration:

```php
<?php

class ActiveController extends \yii\rest\ActiveController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        unset($behaviors['authenticator']);
        unset($behaviors['rateLimiter']);

        /** @var \chervand\yii2\oauth2\server\Module $auth */
        $auth = \Yii::$app->getModule('oauth2');

        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\CompositeAuth::class,
            'authMethods' => [
                [
                    'class' => \chervand\yii2\oauth2\server\components\AuthMethods\HttpMacAuth::class,
                    'publicKey' => $auth->publicKey,
                    'cache' => $auth->cache,
                ],
                [
                    'class' => \chervand\yii2\oauth2\server\components\AuthMethods\HttpBearerAuth::class,
                    'publicKey' => $auth->publicKey,
                    'cache' => $auth->cache,
                ],
            ]
        ];

        $behaviors['rateLimiter'] = [
            'class' => \yii\filters\RateLimiter::class,
        ];

        return $behaviors;
    }

}
```

### RBAC

TBA
