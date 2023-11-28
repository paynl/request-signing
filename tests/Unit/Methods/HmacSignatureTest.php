<?php

declare(strict_types=1);

namespace PayNL\RequestSigning\Tests\Unit\Methods;

use Nyholm\Psr7\Request;
use PayNL\RequestSigning\Constant\SignatureAlgorithm;
use PayNL\RequestSigning\Constant\SignatureMethodEnum;
use PayNL\RequestSigning\Exception\SignatureKeyNotFoundException;
use PayNL\RequestSigning\Exception\UnsupportedHashingAlgorithmException;
use PayNL\RequestSigning\Methods\HmacSignature;
use PayNL\RequestSigning\Methods\RequestSigningMethodInterface;
use PayNL\RequestSigning\Repository\HmacSignatureKeyRepositoryInterface;
use PayNL\RequestSigning\ValueObject\SignatureKey;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class HmacSignatureTest extends TestCase
{
    private const SIGNATURE_KEY_ID = 'SL-1234-1234';
    private const KEY_SECRET = '150a14ed5bea6cc731cf86c41566ac427a8db48ef1b9fd626664b3bfbb99071fa4c922f33dde38719b8c8354e2b7ab9d77e0e67fc12843920a712e73d558e197';
    private const SIGNATURE_ALGORITHM = 'sha512';

    /**
     * @throws SignatureKeyNotFoundException
     * @throws UnsupportedHashingAlgorithmException
     */
    public function testItCanCreateASignature(): void
    {
        // First, we'll instantiate the HMAC Signature class
        $signingMethod = new HmacSignature($this->getKeyRepository($this->getDummySignatureKey()));

        // Next, we'll sign a dummy request
        $signedRequest = $signingMethod->sign($this->getDummyRequest(), self::SIGNATURE_KEY_ID, self::SIGNATURE_ALGORITHM);

        $this->assertInstanceOf(RequestInterface::class, $signedRequest);
        $this->assertEquals(self::SIGNATURE_KEY_ID, $signedRequest->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_KEY_ID_HEADER));
        $this->assertEquals(self::SIGNATURE_ALGORITHM, $signedRequest->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_ALGORITHM_HEADER));
        $this->assertEquals(SignatureMethodEnum::HMAC, $signedRequest->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_METHOD_HEADER));
        $this->assertNotEmpty($signedRequest->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_HEADER));
    }

    /**
     * @throws SignatureKeyNotFoundException
     */
    public function testItThrowsAnExceptionWithUnsupportedHashingAlgorithm(): void
    {
        $this->expectException(UnsupportedHashingAlgorithmException::class);

        (new HmacSignature($this->getKeyRepository($this->getDummySignatureKey())))->sign($this->getDummyRequest(), self::SIGNATURE_KEY_ID, 'Unknown Algorithm');
    }

    public function testItCanVerifyAGivenRequest(): void
    {
        // First, we'll create a dummy request
        $request = $this->getSignedDummyRequest();

        // Next, we'll instantiate the HmacSignature class
        $hmacSignature = new HmacSignature($this->getKeyRepository($this->getDummySignatureKey()));

        // Next, we'll verify that this request passes its verification
        $this->assertTrue($hmacSignature->verify($request));
    }

    public function testItWillFailForAnInvalidSignature(): void
    {
        // First, we'll create a dummy request
        $request = $this->getDummyRequest()
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_ALGORITHM_HEADER, self::SIGNATURE_ALGORITHM)
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_METHOD_HEADER, SignatureMethodEnum::HMAC)
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_KEY_ID_HEADER, self::SIGNATURE_KEY_ID)
            ->withHeader(
                RequestSigningMethodInterface::SIGNATURE_HEADER,
                'Not a signature'
            );

        // Next, we'll instantiate the HmacSignature class
        $hmacSignature = new HmacSignature($this->getKeyRepository($this->getDummySignatureKey()));

        // Next, we'll verify that this request passes its verification
        $this->assertFalse($hmacSignature->verify($request));
    }

    public function testItReturnsFalseForAnUnknownKey(): void
    {
        // Next, we'll mock a repository that throws an "SignatureKeyNotFound" exception
        $repository = $this->createMock(HmacSignatureKeyRepositoryInterface::class);
        $repository->method('findOneById')->willThrowException(SignatureKeyNotFoundException::forKeyId(self::SIGNATURE_KEY_ID));

        // Next, we'll instantiate the HmacSignature class
        $hmacSignature = new HmacSignature($repository);

        // Next, we'll call the verify method, this should return false as it couldn't find the key
        $this->assertFalse($hmacSignature->verify($this->getSignedDummyRequest()));
    }

    public function testItReturnsFalseForAnHashingAlgorithm(): void
    {
        // First, we'll mock a request that has an unkown algorithm in its header
        $request = $this->getSignedDummyRequest()
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_ALGORITHM_HEADER, 'Unknown algorithm');

        // Next, we'll instantiate the HmacSignature class
        $hmacSignature = new HmacSignature($this->getKeyRepository($this->getDummySignatureKey()));

        // Next, we'll call the verify method, this should return false as it couldn't find the key
        $this->assertFalse($hmacSignature->verify($request));
    }

    private function getDummyRequest(): RequestInterface
    {
        return new Request('POST', 'https://pay.nl');
    }

    private function getSignedDummyRequest(): RequestInterface
    {
        return $this->getDummyRequest()
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_ALGORITHM_HEADER, self::SIGNATURE_ALGORITHM)
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_METHOD_HEADER, SignatureMethodEnum::HMAC)
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_KEY_ID_HEADER, self::SIGNATURE_KEY_ID)
            ->withHeader(
                RequestSigningMethodInterface::SIGNATURE_HEADER,
                '6086a395c7fe9b47f47cee4623b76cc4cc79cb3f44e968091edc447cfe7e7533dc2564f49b22bc1f225c24f172be0d2a5b5893e0825c6fde782a63f3e43ce7d1'
            );
    }

    private function getDummySignatureKey(): SignatureKey
    {
        return new SignatureKey(self::SIGNATURE_KEY_ID, self::KEY_SECRET);
    }

    private function getKeyRepository(?SignatureKey $signatureKey = null): HmacSignatureKeyRepositoryInterface
    {
        $repository = $this->createMock(HmacSignatureKeyRepositoryInterface::class);
        $repository->method('findOneById')->willReturn($signatureKey);

        return $repository;
    }
}
