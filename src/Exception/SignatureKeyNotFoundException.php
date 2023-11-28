<?php

declare(strict_types=1);

namespace PayNL\RequestSigning\Exception;

use Exception;

final class SignatureKeyNotFoundException extends Exception
{
    public static function forKeyId(string $keyId): self
    {
        return new self(sprintf('No key found for KeyID [%s].', $keyId));
    }
}
