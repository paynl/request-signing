<?php

declare(strict_types=1);

namespace PayNL\RequestSigning\Exception;

use Exception;

final class UnsupportedHashingAlgorithmException extends Exception
{
    public static function forAlgorithm(string $algorithm): self
    {
        return new self(sprintf('Unsupported hashing algorithm [%s].', $algorithm));
    }
}
