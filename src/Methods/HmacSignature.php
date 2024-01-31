<?php

declare(strict_types=1);

namespace PayNL\RequestSigning\Methods;

use PayNL\RequestSigning\Constant\SignatureMethodEnum;
use PayNL\RequestSigning\Exception\SignatureKeyNotFoundException;
use PayNL\RequestSigning\Exception\UnsupportedHashingAlgorithmException;
use PayNL\RequestSigning\Repository\HmacSignatureKeyRepositoryInterface;
use PayNL\RequestSigning\ValueObject\SignatureData;
use Psr\Http\Message\RequestInterface;
use Throwable;

final class HmacSignature implements RequestSigningMethodInterface
{
    public const METHOD_NAME = SignatureMethodEnum::HMAC;

    private HmacSignatureKeyRepositoryInterface $signatureKeyRepository;

    public function __construct(HmacSignatureKeyRepositoryInterface $signatureKeyRepository)
    {
        $this->signatureKeyRepository = $signatureKeyRepository;
    }

    /**
     * Generate a signature for a given request using the specified key and algorithm.
     *
     * @param RequestInterface $request
     * @param string $keyId The ID of the key used for signing the request.
     * @param string $algorithm The hashing algorithm to be used for signing the request.
     *
     * @throws UnsupportedHashingAlgorithmException If the specified algorithm is not supported.
     * @throws SignatureKeyNotFoundException if the key for the given key id cannot be found
     *
     * @return RequestInterface The generated signature data.
     */
    public function sign(RequestInterface $request, string $keyId, string $algorithm): RequestInterface
    {
        $signatureData = $this->generateSignature((string) $request->getBody(), $keyId, $algorithm);

        return $request
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_HEADER, $signatureData->getSignature())
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_KEY_ID_HEADER, $signatureData->getKeyId())
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_METHOD_HEADER, $signatureData->getMethod())
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_ALGORITHM_HEADER, $signatureData->getAlgorithm());
    }

    /**
     * Generate a signature using the given body, key ID, and algorithm.
     *
     * @param string $body The body to sign.
     * @param string $keyId The ID of the key to use for signing.
     * @param string $algorithm The hashing algorithm to use for signing.
     *
     * @throws UnsupportedHashingAlgorithmException if the provided algorithm is not supported.
     * @throws SignatureKeyNotFoundException if the key for the given key id cannot be found
     * @return SignatureData The generated signature data.
     */
    private function generateSignature(string $body, string $keyId, string $algorithm): SignatureData
    {
        $key = $this->signatureKeyRepository->findOneById($keyId);

        $algorithm = strtolower($algorithm);

        if (in_array($algorithm, hash_hmac_algos()) === false) {
            throw UnsupportedHashingAlgorithmException::forAlgorithm($algorithm);
        }

        return new SignatureData(
            hash_hmac($algorithm, $body, $key->getSecret()),
            $keyId,
            $algorithm,
            self::METHOD_NAME
        );
    }

    /**
     * Verifies the signature of a request.
     *
     * @param RequestInterface $request The request to verify.
     *
     * @return bool Returns true if the signature is valid, false otherwise.
     */
    public function verify(RequestInterface $request): bool
    {
        try {
            $keyId = $request->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_KEY_ID_HEADER);
            $signature = $request->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_HEADER);
            $algorithm = $request->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_ALGORITHM_HEADER);

            $generatedSignature = $this
                ->generateSignature((string) $request->getBody(), $keyId, $algorithm)
                ->getSignature();

            return hash_equals($signature, $generatedSignature);
        } catch (Throwable $throwable) {
            // We do nothing with the exception. The request stays marked as "invalid".
        }

        return false;
    }

    public function supports(string $method): bool
    {
        return strtolower(self::METHOD_NAME) === strtolower($method);
    }
}
