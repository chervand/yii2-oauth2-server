# Yii2 OAuth Server

`chervand/yii2-oauth2-server` is a Yii2 PHP Framework integration of n integration of [`thephpleague/oauth2-server`](https://github.com/thephpleague/oauth2-server).
It additionally provides `MAC` tokens support.

## Contributions and roadmap

It currently supports only `client_credentials` grant with `Bearer` and `MAC` tokens (both `JWT` signed with `RSA` key).

Contributions are welcome, so if you want to implement any missing feature or add any improvement, please open a PR against `master` branch.

## Setup

### Apply DB migrations

    ./yii migrate --migrationPath="@vendor/chervand/yii2-oauth2-server/migrations"

### Generating public and private keys

See [OAuth 2.0 Server installation](https://oauth2.thephpleague.com/installation/) page.

### Authorization server

Configure the module:

```php
...
    `modules` = [
        'oauth2' => [
            'class' => \chervand\yii2\oauth2\server\Module::class,
            'privateKey' => __DIR__ . '/../private.key',
            'publicKey' => __DIR__ . '/../public.key',
            'userRepository' => \app\components\UserRepository::class,
            'enabledGrantTypes' => [
                new \League\OAuth2\Server\Grant\ImplicitGrant(new \DateInterval('PT1H')),
                new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
            ],
        ],
...
```

Add the module to application's `bootstrap`:

```php
...
'bootstrap' => ['oauth2'],
...
```

### Resource server

Configure controllers behaviors:

```php
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'authMethods' => [
                [
                    'class' => HttpMacAuth::class,
                    'publicKey' => $publicKey
                ],
                [
                    'class' => HttpBearerAuth::class,
                    'publicKey' => $publicKey
                ],
            ]
        ];
        return $behaviors;
    }
```
