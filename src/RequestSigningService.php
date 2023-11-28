<?php

declare(strict_types=1);

namespace PayNL\RequestSigning;

use PayNL\RequestSigning\Exception\SignatureKeyNotFoundException;
use PayNL\RequestSigning\Exception\UnknownSigningMethodException;
use PayNL\RequestSigning\Exception\UnsupportedHashingAlgorithmException;
use PayNL\RequestSigning\Methods\HmacSignature;
use PayNL\RequestSigning\Methods\RequestSigningMethodInterface;
use PayNL\RequestSigning\Repository\HmacSignatureKeyRepositoryInterface;
use Psr\Http\Message\RequestInterface;

final class RequestSigningService
{
    /** @var RequestSigningMethodInterface[] */
    private array $requestSigningMethods;

    /** @param RequestSigningMethodInterface[] $requestSigningMethods */
    public function __construct(array $requestSigningMethods)
    {
        $this->requestSigningMethods = $requestSigningMethods;
    }

    /**
     * Signs a request using the given key id, algorithm, and method.
     *
     * @param RequestInterface $request The request to be signed.
     * @param string $keyId The ID of the key to be used for signing.
     * @param string $algorithm The algorithm to be used for signing.
     * @param string $method The method to be used for signing (e.g., HMAC, RSA, etc.).
     *
     * @throws UnknownSigningMethodException if the given signing method is not known
     * @throws UnsupportedHashingAlgorithmException If the specified algorithm is not supported.
     * @throws SignatureKeyNotFoundException if the key for the given key id cannot be found
     *
     * @return RequestInterface The signed request.
     */
    public function sign(RequestInterface $request, string $keyId, string $algorithm, string $method): RequestInterface
    {
        return $this->getSignatureMethod($method)->sign($request, $keyId, $algorithm);
    }

    /**
     * Verifies the authenticity of the given request.
     *
     * @param RequestInterface|null $request The request to be verified.
     *  If not provided, a new request will be created from globals.
     *
     * @throws UnknownSigningMethodException if the given signing method is not known
     *
     * @return bool True if the request is valid, false otherwise.
     */
    public function verify(?RequestInterface $request = null): bool
    {
        if ($request instanceof RequestInterface === false) {
            $request = create_psr_server_request();
        }

        return $this->getSignatureMethodFromRequest($request)->verify($request);
    }

    /**
     * Retrieves the signature method from the request.
     *
     * @param RequestInterface $request The request object.
     *
     * @return RequestSigningMethodInterface The signature method object.
     */
    private function getSignatureMethodFromRequest(RequestInterface $request): RequestSigningMethodInterface
    {
        return $this->getSignatureMethod(
            $request->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_METHOD_HEADER)
        );
    }

    /**
     * Returns the RequestSigningMethodInterface based on the given method name.
     *
     * @param string $method The method name.
     *
     * @throws UnknownSigningMethodException When an unknown method is provided.
     * @return RequestSigningMethodInterface The RequestSigningMethodInterface object.
     */
    private function getSignatureMethod(string $method): RequestSigningMethodInterface
    {
        foreach ($this->requestSigningMethods as $requestSigningMethod) {
            if (
                $requestSigningMethod instanceof RequestSigningMethodInterface &&
                $requestSigningMethod->supports($method)
            ) {
                return $requestSigningMethod;
            }
        }

        throw UnknownSigningMethodException::forUnknownMethod($method);
    }
}
