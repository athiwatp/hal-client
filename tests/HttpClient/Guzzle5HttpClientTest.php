<?php

namespace Jsor\HalClient\HttpClient;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Message\Request as GuzzleRequest;
use GuzzleHttp\Message\Response as GuzzleResponse;
use Jsor\HalClient\HalClient;
use Jsor\HalClient\TestCase;

class Guzzle5HttpClientTest extends TestCase
{
    public function setUp()
    {
        if (version_compare(GuzzleClientInterface::VERSION, '5.0.0', '<') ||
            version_compare(GuzzleClientInterface::VERSION, '6.0.0', '>=')) {
            $this->markTestIncomplete('GuzzleHttp version other than ~5.0 installed (Installed version ' . GuzzleClientInterface::VERSION . ').');
        }
    }

    /**
     * @test
     */
    public function it_will_call_send()
    {
        $guzzleRequest  = new GuzzleRequest('GET', '/', []);
        $guzzleResponse = new GuzzleResponse(200, ['Content-Type' => 'application/hal+json']);

        $guzzleClient = $this->getMock('GuzzleHttp\ClientInterface');

        $guzzleClient
            ->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($guzzleRequest));

        $guzzleClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($guzzleResponse));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            new Guzzle5HttpClient($guzzleClient)
        );

        $client->request('GET', '/');
    }

    /**
     * @test
     * @expectedException \Jsor\HalClient\Exception\BadResponseException
     */
    public function it_will_transform_exception()
    {
        $guzzleRequest = new GuzzleRequest('GET', '/', []);

        $guzzleClient = $this->getMock('GuzzleHttp\ClientInterface');

        $guzzleClient
            ->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($guzzleRequest));

        $guzzleClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($request) {
                throw GuzzleRequestException::create($request);
            }));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            new Guzzle5HttpClient($guzzleClient)
        );

        $client->request('GET', '/');
    }

    /**
     * @test
     * @expectedException \Jsor\HalClient\Exception\BadResponseException
     */
    public function it_will_transform_exception_with_response()
    {
        $guzzleRequest = new GuzzleRequest('GET', '/', []);

        $guzzleClient = $this->getMock('GuzzleHttp\ClientInterface');

        $guzzleClient
            ->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($guzzleRequest));

        $guzzleClient
            ->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($request) {
                throw GuzzleRequestException::create(
                    $request,
                    new GuzzleResponse(500)
                );
            }));

        $client = new HalClient(
            'http://propilex.herokuapp.com',
            new Guzzle5HttpClient($guzzleClient)
        );

        $client->request('GET', '/');
    }
}