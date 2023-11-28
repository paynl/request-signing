<?php

declare(strict_types=1);

namespace PayNL\RequestSigning\ValueObject;

final class SignatureKey
{
    private string $id;

    private string $secret;

    public function __construct(string $id, string $secret)
    {
        $this->id = $id;
        $this->secret = $secret;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
