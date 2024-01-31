<?php

declare(strict_types=1);

namespace PayNL\RequestSigning\Tests\Unit;

use Generator;
use PayNL\RequestSigning\Constant\SignatureMethodEnum;
use PayNL\RequestSigning\Exception\UnknownSigningMethodException;
use PayNL\RequestSigning\Methods\HmacSignature;
use PayNL\RequestSigning\Methods\RequestSigningMethodInterface;
use PayNL\RequestSigning\Repository\HmacSignatureKeyRepositoryInterface;
use PayNL\RequestSigning\RequestSigningService;
use PayNL\RequestSigning\ValueObject\SignatureKey;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Nyholm\Psr7\Request;

final class RequestSigningServiceTest extends TestCase
{
    private const SIGNATURE_KEY_ID = 'SL-1234-1234';
    private const KEY_SECRET = '150a14ed5bea6cc731cf86c41566ac427a8db48ef1b9fd626664b3bfbb99071fa4c922f33dde38719b8c8354e2b7ab9d77e0e67fc12843920a712e73d558e197';
    private const SIGNATURE_ALGORITHM = 'sha512';

    /** @dataProvider requestSigningDataProvider */
    public function testItCanSignARequest(Request $request, SignatureKey $signatureKey, string $algorithm, string $method): void
    {
        $repository = $this->createMock(HmacSignatureKeyRepositoryInterface::class);
        $repository->method('findOneById')->willReturn($signatureKey);

        $signedRequest = (new RequestSigningService([new HmacSignature($repository)]))->sign($request, $signatureKey->getId(), $algorithm, $method);

        $this->assertInstanceOf(RequestInterface::class, $signedRequest);
        $this->assertEquals($signatureKey->getId(), $signedRequest->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_KEY_ID_HEADER));
        $this->assertEquals($algorithm, $signedRequest->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_ALGORITHM_HEADER));
        $this->assertEquals($method, $signedRequest->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_METHOD_HEADER));
        $this->assertNotEmpty($signedRequest->getHeaderLine(RequestSigningMethodInterface::SIGNATURE_HEADER));
    }

    /** @dataProvider requestVerificationDataProvider */
    public function testItCanVerifyARequest(Request $request, SignatureKey $signatureKey): void
    {
        $repository = $this->createMock(HmacSignatureKeyRepositoryInterface::class);
        $repository->method('findOneById')->willReturn($signatureKey);

        $this->assertTrue((new RequestSigningService([new HmacSignature($repository)]))->verify($request));
    }

    public function testItThrowsAnExceptionOnAnUnknownSigningMethod(): void
    {
        $this->expectException(UnknownSigningMethodException::class);

        $repository = $this->createMock(HmacSignatureKeyRepositoryInterface::class);
        $repository->method('findOneById')->willReturn(self::getDummySignatureKey());

        (new RequestSigningService([new HmacSignature($repository)]))->verify();
    }

    private static function getDummyRequest(): RequestInterface
    {
        return new Request('POST', 'https://pay.nl');
    }

    private static function getHmacSignedDummyRequest(): RequestInterface
    {
        return self::getDummyRequest()
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_ALGORITHM_HEADER, self::SIGNATURE_ALGORITHM)
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_METHOD_HEADER, SignatureMethodEnum::HMAC)
            ->withHeader(RequestSigningMethodInterface::SIGNATURE_KEY_ID_HEADER, self::SIGNATURE_KEY_ID)
            ->withHeader(
                RequestSigningMethodInterface::SIGNATURE_HEADER,
                '1e23c01aec9410779fb4efdcd8582ff66d3d8f99b17060272d564e05c33b475770ce5d2277ee4fb1917c0716e10d1533f4b4925e4870e2cd1f0884f12f1de793'
            );
    }

    private static function getDummySignatureKey(): SignatureKey
    {
        return new SignatureKey(self::SIGNATURE_KEY_ID, self::KEY_SECRET);
    }

    public static function requestSigningDataProvider(): Generator
    {
        yield 'HMAC' => [
            'request' => self::getDummyRequest(),
            'signatureKey' => self::getDummySignatureKey(),
            'algorithm' => self::SIGNATURE_ALGORITHM,
            'method' => SignatureMethodEnum::HMAC,
        ];
    }

    public static function requestVerificationDataProvider(): Generator
    {
        yield 'HMAC' => [
            'request' => self::getHmacSignedDummyRequest(),
            'signatureKey' => self::getDummySignatureKey(),
        ];
    }
}
