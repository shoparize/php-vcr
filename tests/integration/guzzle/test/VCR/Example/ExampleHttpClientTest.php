<?php

namespace VCR\Example;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * Tests example request.
 */
class ExampleHttpClientTest extends TestCase
{
    public const TEST_GET_URL = 'https://httpbin.org/get';
    public const TEST_POST_URL = 'https://httpbin.org/post';
    public const TEST_POST_BODY = '{"foo":"bar"}';

    protected $ignoreHeaders = [
        'Accept',
        'Connect-Time',
        'Total-Route-Time',
        'X-Request-Id',
    ];

    protected function setUp(): void
    {
        vfsStream::setup('testDir');
        \VCR\VCR::configure()->setCassettePath(vfsStream::url('testDir'));
    }

    public function testRequestGET(): void
    {
        \VCR\VCR::turnOn();
        \VCR\VCR::insertCassette('test-cassette.yml');
        $originalRequest = $this->requestGET();
        $this->assertValidGETResponse($originalRequest);
        $interceptedRequest = $this->requestGET();
        $this->assertValidGETResponse($interceptedRequest);
        $this->assertEquals($originalRequest, $interceptedRequest);
        $repeatInterceptedRequest = $this->requestGET();
        $this->assertEquals($interceptedRequest, $repeatInterceptedRequest);
        \VCR\VCR::turnOff();
    }

    public function testRequestPOST(): void
    {
        \VCR\VCR::turnOn();
        \VCR\VCR::insertCassette('test-cassette.yml');
        $originalRequest = $this->requestPOST();
        $this->assertValidPOSTResponse($originalRequest);
        $interceptedRequest = $this->requestPOST();
        $this->assertValidPOSTResponse($interceptedRequest);
        $this->assertEquals($originalRequest, $interceptedRequest);
        $repeatInterceptedRequest = $this->requestPOST();
        $this->assertEquals($interceptedRequest, $repeatInterceptedRequest);
        \VCR\VCR::turnOff();
    }

    protected function requestGET()
    {
        $exampleClient = new ExampleHttpClient();

        $response = $exampleClient->get(self::TEST_GET_URL);

        return $response;
    }

    protected function requestPOST()
    {
        $exampleClient = new ExampleHttpClient();

        $response = $exampleClient->post(self::TEST_POST_URL, self::TEST_POST_BODY);
        foreach ($this->ignoreHeaders as $header) {
            unset($response['headers'][$header]);
        }
        unset($response['origin']);

        return $response;
    }

    protected function requestPOSTIntercepted()
    {
        \VCR\VCR::turnOn();
        \VCR\VCR::insertCassette('test-cassette.yml');
        $info = $this->requestPOST();
        \VCR\VCR::turnOff();

        return $info;
    }

    protected function assertValidGETResponse($info): void
    {
        $this->assertIsArray($info, 'Response is not an array.');
        $this->assertArrayHasKey('url', $info, 'API did not return any value.');
    }

    protected function assertValidPOSTResponse($info): void
    {
        $this->assertIsArray($info, 'Response is not an array.');
        $this->assertArrayHasKey('url', $info, "Key 'url' not found.");
        $this->assertEquals(self::TEST_POST_URL, $info['url'], "Value for key 'url' wrong.");
        $this->assertArrayHasKey('headers', $info, "Key 'headers' not found.");
        $this->assertIsArray($info['headers'], 'Headers is not an array.');
        $this->assertEquals(self::TEST_POST_BODY, $info['data'], 'Correct request body was not sent.');
    }
}
