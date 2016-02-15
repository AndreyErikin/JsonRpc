<?php

namespace JsonRpc\Test\Transport;

use JsonRpc\Transport\PhpTransport;
use JsonRpc\Transport\TransportException;

/**
 * Class PhpTransportTest
 * @package JsonRpc\Test\Transport
 */
class PhpTransportTest extends \PHPUnit_Framework_TestCase
{
    const REQUEST_METHOD_CORRECT = 'POST';
    const REQUEST_METHOD_ERROR = 'GET';
    const QUERY_STRING = '';
    const SERVER_PHP_SELF = '';
    const CONTENT_TYPE_CORRECT = 'application/json-rpc; charset=utf-8';
    const CONTENT_TYPE_ERROR = 'error';

    /**
     * @var string
     */
    protected $log = '';

    /**
     * @var string
     */
    protected $url = '';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $json = '';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $_SERVER['PHP_SELF'] = self::SERVER_PHP_SELF;
        $_SERVER['QUERY_STRING'] = self::QUERY_STRING;

        $this->log = '';
        $this->url = 'SomeTestURL';
        $this->data = [
            'number1' => 1,
            'number2' => 19.56,
            'true'    => true,
            'false'   => false,
            'null'    => null,
            'string'  => 'text',
            'array'   => [
                1,
                true,
                false,
                null,
                'text'
            ],
            'object'  => [
                'param' => 'value',
            ],
        ];

        $this->json = json_encode($this->data);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockBuilder
     */
    private function prepareTransportMock()
    {
        return $this->getMockBuilder('\JsonRpc\Transport\PhpTransport')
            ->setConstructorArgs([[$this, 'transportCallback'], [$this, 'transportCallback']]);
    }

    /**
     * @return \JsonRpc\Transport\PhpTransport
     */
    protected function getTransportMock()
    {
        return $this->prepareTransportMock()
            ->getMock();
    }

    /**
     * @param array $data
     * @return \JsonRpc\Transport\PhpTransport
     */
    protected function getMockSendRequest(array $data)
    {
        $mock = $this->prepareTransportMock()
            ->setMethods(['sendRequest'])
            ->getMock();

        $mock->expects($this->any())
            ->method('sendRequest')
            ->willReturn($data);

        return $mock;
    }

    /**
     * @return \JsonRpc\Transport\PhpTransport
     */
    protected function getMockSendResponse()
    {
        $mock = $this->prepareTransportMock()
            ->setMethods(['sendResponse'])
            ->getMock();

        $mock->expects($this->any())
            ->method('sendRequest')
            ->willReturn(null);

        return $mock;
    }

    /**
     * @param mixed $data
     * @return \JsonRpc\Transport\PhpTransport
     */
    protected function getMockGetRequest($data = null)
    {
        $mock = $this->prepareTransportMock()
            ->setMethods(['getRequest'])
            ->getMock();

        $mock->expects($this->any())
            ->method('getRequest')
            ->willReturn($data);

        return $mock;
    }

    /**
     * @return PhpTransport
     */
    protected function getTransportObject()
    {
        return new PhpTransport([$this, 'transportCallback'], [$this, 'transportCallback']);
    }

    /**
     * @param string $action
     * @param string $message
     */
    public function transportCallback($action, $message)
    {
        $this->log .= str_replace("\n", "", "[{$action}]{$message}");
    }

    /**
     * @throws \JsonRpc\Transport\TransportException
     */
    public function testSendRequest()
    {
        $expected = [
            'http_code' => 200,
            'response'  => $this->json,
        ];
        $transport = $this->getMockSendRequest($expected);

        $this->assertEquals($this->data, $transport->send(false, $this->data, $this->url));
        $this->assertAttributeEquals($this->json, 'request', $transport);
        $this->assertAttributeEquals($expected, 'response', $transport);
    }

    /**
     * @expectedException \JsonRpc\Transport\TransportException
     * @expectedExceptionCode    -32700
     * @expectedExceptionMessage Parse error.
     */
    public function testSendWrongRequest()
    {
        $this->getMockSendRequest([
            'http_code' => 500,
            'response'  => 'Error data'
        ])->send(false, [], $this->url);
    }

    /**
     *
     */
    public function testSendNotification()
    {
        $transport = $this->getMockSendRequest([
            'http_code' => 200,
            'response'  => $this->json,
        ]);
        $result = $transport->send(true, $this->data, $this->url);

        $this->assertEquals(true, $result);
        $this->assertEquals(200, $transport->response['http_code']);
    }

    /**
     *
     */
    public function testSendWrongNotification()
    {
        $transport = $this->getMockSendRequest([
            'http_code' => 500,
            'response'  => 'Error data'
        ]);
        $result = $transport->send(true, $this->data, $this->url);

        $this->assertEquals(false, $result);
        $this->assertEquals(500, $transport->response['http_code']);
    }

    /**
     *
     */
    public function testReceive()
    {
        $_SERVER['REQUEST_METHOD'] = self::REQUEST_METHOD_CORRECT;
        $_SERVER['CONTENT_TYPE'] = self::CONTENT_TYPE_CORRECT;

        $transport = $this->getMockGetRequest($this->json);
        $result = $transport->receive();

        $this->assertEquals($this->data, $result);
        $this->assertEquals("[request]{$this->json}", $this->log);
    }

    /**
     * @expectedException \JsonRpc\Transport\TransportException
     * @expectedExceptionCode    -32700
     * @expectedExceptionMessage Parse error.
     */
    public function testReceiveWrongResponse()
    {
        $_SERVER['REQUEST_METHOD'] = self::REQUEST_METHOD_CORRECT;
        $_SERVER['CONTENT_TYPE'] = self::CONTENT_TYPE_CORRECT;

        $this->getMockGetRequest('Wrong data')->receive();
    }

    /**
     * @expectedException \JsonRpc\Transport\TransportException
     * @expectedExceptionCode    -32600
     * @expectedExceptionMessage Invalid Request.
     */
    public function testReceiveWrongContentType()
    {
        $_SERVER['REQUEST_METHOD'] = self::REQUEST_METHOD_CORRECT;
        $_SERVER['CONTENT_TYPE'] = self::CONTENT_TYPE_ERROR;

        $this->getTransportObject()->receive();
    }

    /**
     * @expectedException \JsonRpc\Transport\TransportException
     * @expectedExceptionCode    -32600
     * @expectedExceptionMessage Invalid Request.
     */
    public function testReceiveWrongMethod()
    {
        $_SERVER['REQUEST_METHOD'] = self::REQUEST_METHOD_ERROR;
        $_SERVER['CONTENT_TYPE'] = self::CONTENT_TYPE_CORRECT;

        $this->getTransportObject()->receive();
    }

    /**
     *
     */
    public function testRespond()
    {
        $this->getMockSendResponse()->respond($this->data, null);
        $this->assertEquals("[response]{$this->json}", $this->log);
    }

    /**
     *
     */
    public function testRespondError()
    {
        $this->getMockSendResponse()->respond($this->data, TransportException::CODE_INTERNAL_ERROR);
        $this->assertEquals("[response]{$this->json}", $this->log);
    }
}
