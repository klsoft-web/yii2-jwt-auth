# YII2-JWT-AUTH

 The package provides a Yii 2 authentication method based on a JWT token.
 
 See also:
 
 -  [YII2-KEYCLOAK-AUTHZ](https://github.com/klsoft-web/yii2-keycloak-authz) - The package provides Keycloak authorization for the web service APIs of Yii 2
 -  [PHP-KEYCLOAK-CLIENT](https://github.com/klsoft-web/php-keycloak-client) - A PHP library that can be used to secure web applications with Keycloak

## Requirement

 - PHP 8.0 or higher.

## Installation

```bash
composer require klsoft/yii2-jwt-auth
```

## How to use

The package requires the implementation of the findIdentityByAccessToken method of Yii\Web\IdentityInterface.

### 1. Implement Klsoft\Yii2JwtAuth\JwksRepositoryInterface

Example:

```php
namespace MyNamespace;

use Yii;
use Klsoft\Yii2JwtAuth\JwksRepositoryInterface;

class JwksRepository implements JwksRepositoryInterface
{
    private const JWKS = 'jwks';

    public function __construct(
        private string $jwksUrl,
        private int    $jwksCacheDuration)
    {
    }

    function getKeys(): ?array
    {
        $keys = Yii::$app->cache->getOrSet(JwksRepository::JWKS, function () {
            $options = [
                'http' => [
                    'method' => 'GET'
                ],
            ];
            $responseData = file_get_contents($this->jwksUrl, false, stream_context_create($options));
            if (!empty($responseData)) {
                return json_decode($responseData, true);
            }
            return [];
        }, $this->jwksCacheDuration);

        if (empty($keys)) {
            Yii::$app->cache->delete(JwksRepository::JWKS);
            return null;
        } else {
            return $keys;
        }
    }
}
```

### 2. Add the JWKS  URL to param.php

Example:

```php
return [
    'jwksUrl' => 'http://localhost:8080/realms/myrealm/protocol/openid-connect/certs',
    'jwksCacheDuration' => 60 * 3
];
```

### 3. Register dependencies

Example of registering dependencies using the application configuration:

```php
'container' => [
        'definitions' => [
            'Klsoft\Yii2JwtAuth\HttpJwtAuth' => [
                'Klsoft\Yii2JwtAuth\HttpJwtAuth',
                [Instance::of('Klsoft\Yii2JwtAuth\JwksRepositoryInterface')]
            ],
        ],
        'singletons' => [
            'Klsoft\Yii2JwtAuth\JwksRepositoryInterface' => [
                'MyNamespace\JwksRepository',
                [
                    $params['jwksUrl'],
                    $params['jwksCacheDuration']
                ]
            ]
        ]
    ]
```

### 4. Configure the `authenticator` behavior

Example:

```php
use yii\rest\Controller;
use Klsoft\Yii2JwtAuth\HttpJwtAuth;

class MyController extends Controller
{
    public function __construct(private HttpJwtAuth $httpJwtAuth)
    {
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authentication'] = $this->httpJwtAuth;
        return $behaviors;
    }
}
```
