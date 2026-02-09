<?php

namespace Klsoft\Yii2JwtAuth;

interface JwksRepositoryInterface
{
    /**
     * Get JWKS.
     *
     * @return ?array
     */
    public function getKeys(): ?array;
}