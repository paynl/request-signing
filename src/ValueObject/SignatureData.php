<?php

declare(strict_types=1);

namespace PayNL\RequestSigning\ValueObject;

final class SignatureData
{
    private string $signature;

    private string $keyId;

    private string $algorithm;

    private string $method;

    /**
     * @param string $signature
     * @param string $keyId
     * @param string $algorithm
     * @param string $method
     */
    public function __construct(string $signature, string $keyId, string $algorithm, string $method)
    {
        $this->signature = $signature;
        $this->keyId = $keyId;
        $this->algorithm = $algorithm;
        $this->method = $method;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getKeyId(): string
    {
        return $this->keyId;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
