<?php

namespace Klsoft\Yii2JwtAuth;

use InvalidArgumentException;
use DomainException;
use UnexpectedValueException;
use yii\filters\auth\AuthMethod;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

/**
 * HttpJwtAuth is a Yii 2 action filter that supports the authentication method based on a JWT token.
 */
class HttpJwtAuth extends AuthMethod
{
    private JwksRepositoryInterface $jwksRepository;
    private string $headerName = 'Authorization';
    private string $headerTokenPattern = '/^Bearer\s+(.*?)$/';
    private string $realm = 'api';

    /**
     * @param JwksRepositoryInterface $jwksRepository
     */
    public function __construct(JwksRepositoryInterface $jwksRepository)
    {
        $this->jwksRepository = $jwksRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate($user, $request, $response)
    {
        $token = $this->getAuthenticationToken($request);
        if ($token !== null) {
            $jwks = $this->jwksRepository->getKeys();
            if ($jwks != null) {
                try {
                    JWT::decode(
                        $token,
                        JWK::parseKeySet($jwks));

                    return $user->loginByAccessToken($token, get_class($this));
                } catch (InvalidArgumentException|DomainException|UnexpectedValueException $ex) {
                    return null;
                }
            }
        }

        return null;
    }

    private function getAuthenticationToken($request): ?string
    {
        $authHeader = $request->getHeaders()->get($this->headerName);
        if (!empty($authHeader) && preg_match($this->headerTokenPattern, $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function challenge($response)
    {
        $response->getHeaders()->set('WWW-Authenticate', "Bearer realm=\"$this->realm\"");
    }

    /**
     * @param string $realm The HTTP authentication realm.
     *
     * @return self
     */
    public function withRealm(string $realm): self
    {
        $new = clone $this;
        $new->realm = $realm;
        return $new;
    }

    /**
     * @param string $headerName Authorization header name.
     *
     * @return self
     */
    public function withHeaderName(string $headerName): self
    {
        $new = clone $this;
        $new->headerName = $headerName;
        return $new;
    }

    /**
     * @param string $headerTokenPattern Regular expression to use for getting a token from authorization header.
     * Token value should match first capturing group.
     *
     * @return self
     */
    public function withHeaderTokenPattern(string $headerTokenPattern): self
    {
        $new = clone $this;
        $new->headerTokenPattern = $headerTokenPattern;
        return $new;
    }

    /**
     * @param array $optional List of action IDs that this filter will be applied to, but auth failure will not lead to error.
     *
     * @return self
     */
    public function withOptional(array $optional): self
    {
        $new = clone $this;
        $new->optional = $optional;
        return $new;
    }
}
