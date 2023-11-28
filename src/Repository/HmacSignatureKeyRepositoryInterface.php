<?php

namespace PayNL\RequestSigning\Repository;

use PayNL\RequestSigning\Exception\SignatureKeyNotFoundException;
use PayNL\RequestSigning\ValueObject\SignatureKey;

interface HmacSignatureKeyRepositoryInterface
{
    /**
     * Find a SignatureKey by its identifier
     *
     * @param string $keyId The identifier of the SignatureKey to find
     *
     * @throws SignatureKeyNotFoundException if the requested key cannot be found
     *
     * @return SignatureKey The found SignatureKey object
     */
    public function findOneById(string $keyId): SignatureKey;
}
