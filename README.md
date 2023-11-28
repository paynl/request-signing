# Request Signing

[![Static Code Analysis](https://github.com/paynl/request-signing/actions/workflows/code-analysis.yaml/badge.svg)](https://github.com/paynl/request-signing/actions/workflows/code-analysis.yaml)
[![PHPUnit tests](https://github.com/paynl/request-signing/actions/workflows/phpunit.yaml/badge.svg)](https://github.com/paynl/request-signing/actions/workflows/phpunit.yaml)
[![Coverage Status](https://coveralls.io/repos/github/paynl/request-signing/badge.svg?branch=main)](https://coveralls.io/github/paynl/request-signing?branch=main)

This package adds functionality to sign & verify requests sent by the Pay. platform.

## Requirements

To install this package you need:

* PHP >= 7.4;
* composer.

## Installation

```
composer require paynl/psr-server-request
```

## Usage

The `PayNL\RequestSigning\RequestSigningService` simplifies both signing and verifying requests, avoiding the need for manual class instantiation. 

The constructor function of this service requires an array of _SingingMethods_ that you wish to support. Each signing method has different needs and functionality, and which one(s) you opt to support falls under your discretion.

By providing the service with the array of these chosen methods, the service can handle the configuration and functionality. This decouples the setup process from your main application logic, allowing a more streamlined integration.

### Signing
The below code snippet shows the basis of singing a request using this package:

```php
use PayNL\RequestSigning\RequestSigningService;
use PayNL\RequestSigning\Constant\SignatureMethodEnum;
use PayNL\RequestSigning\Methods\HmacSignature;

// Instantiate the RequestSigningService by providing it with your supported SigningMethods
$signingService = new RequestSigningService([
    new HmacSignature(
        new SignatureKeyRepository() // Your implementation of the HmacSignatureKeyRepositoryInterface
    )
]);

// Create your PSR Request, in this example we use the Nyholm PSR7 Request
$request = new \Nyholm\Psr7\Request('POST', 'https://pay.nl', [], '{"hello": "world"}')

// Sign the request providing the id of the key you want to use, the algorithm and the signature method
// This will return the given request with the signature headers attached to it
$signedRequest = $signingService->sign($request, 'SL-1234-1234', 'sha512', SignatureMethodEnum::HMAC);
```

### Verify
The below code snippet shows the basis of verifying a request signed using the above-mentioned method:
```php
use PayNL\RequestSigning\RequestSigningService;

// Instantiate the RequestSigningService by providing it with your supported SigningMethods
$signingService = new RequestSigningService([
    new HmacSignature(
        new SignatureKeyRepository() // Your implementation of the HmacSignatureKeyRepositoryInterface
    )
]);

// Retrieve your request, in this example we'll use the paynl/psr-server-request package to create a PSR Server Request from the PHP Global Variables
$request = create_psr_server_request();

// Pass this request to the verify method. The request argument is optional, if not provided it will attempt to create a request using the above-mentioned method.
$requestValid = $signingService->verify($request);
```

### Exception handling

The request signing service and the underlying signing / verifying methods may throw exceptions when unexpected values are encountered, these are:
- SignatureKeyNotFound, this exception must be thrown when the implementation of the `PayNL\RequestSigning\Repository\SignatureKeyRepositoryInterface` can not find the key based on the provided id;
- UnknownSigningMethodException, this exception will be thrown by the singing / verifying methods when they are requested to sign / verify a request with an algorithm they do not support;
- UnsupportedHashingAlgorithmException, this exception will be thrown by the `PayNL\RequestSigning\RequestSigningService` when it is requested to sign / verify a request with a method it doesn't support.

### Supported Signing / Verifying methods
#### HMAC
The `PayNL\RequestSigning\Methods\HmacSignature` class enables the signing and verification of requests made with HMAC signatures.
To utilize this class's method, a single argument is required for its constructor. This argument is of type `PayNL\RequestSigning\Repository\HmacSignatureKeyRepositoryInterface`.

_That is all you need to know to integrate this package, happy coding!_
