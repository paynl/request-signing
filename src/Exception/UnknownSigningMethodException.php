<?php

declare(strict_types=1);

namespace PayNL\RequestSigning\Exception;

use RuntimeException;

final class UnknownSigningMethodException extends RuntimeException
{
    public static function forUnknownMethod(string $method): self
    {
        return new self(sprintf('Unknown signing method [%s].', $method));
    }
}
