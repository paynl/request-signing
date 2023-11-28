<?php

namespace PayNL\RequestSigning\Methods;

use PayNL\RequestSigning\Exception\SignatureKeyNotFoundException;
use PayNL\RequestSigning\Exception\UnsupportedHashingAlgorithmException;
use PayNL\RequestSigning\ValueObject\SignatureData;
use Psr\Http\Message\RequestInterface;

interface RequestSigningMethodInterface
{
    public const SIGNATURE_HEADER = 'Signature';
    public const SIGNATURE_KEY_ID_HEADER = 'Signature-KeyID';
    public const SIGNATURE_METHOD_HEADER = 'Signature-Method';
    public const SIGNATURE_ALGORITHM_HEADER = 'Signature-Algorithm';

    /**
     * Generate a signature for a given request using the specified key and algorithm.
     *
     * @param RequestInterface $request the request to sign
     * @param string $keyId The ID of the key used for signing the request.
     * @param string $algorithm The hashing algorithm to be used for signing the request.
     *
     * @throws UnsupportedHashingAlgorithmException If the specified algorithm is not supported.
     * @throws SignatureKeyNotFoundException if the key for the given key id cannot be found
     *
     * @return RequestInterface The generated signature data.
     */
    public function sign(RequestInterface $request, string $keyId, string $algorithm): RequestInterface;

    /**
     * Verifies the signature of a request.
     *
     * @param RequestInterface $request The request to verify.
     *
     * @return bool Returns true if the signature is valid, false otherwise.
     */
    public function verify(RequestInterface $request): bool;

    /**
     * Check if given method is supported.
     *
     * @param string $method The method to check for support.
     *
     * @return bool Whether the method is supported or not.
     */
    public function supports(string $method): bool;
}
