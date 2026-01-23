<?php

namespace Klsoft\Yii2JwtAuth;

interface JwksRepositoryInterface
{
    /**
     * Get JWKS.
     *
     * @return ?array
     */
    function getKeys(): ?array;
}