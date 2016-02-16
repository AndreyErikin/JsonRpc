<?php
namespace JsonRpc\Test;

use JsonRpc\Client;
use JsonRpc\ClientException;
use JsonRpc\Transport\PhpTransport;

/**
 * Class ClientTest
 * @package JsonRpc\Test
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    const CONTENT_TYPE          = 'Content-Type: application/json-rpc; charset=utf-8';
    const CALL_METHOD           = 'test.test';
    const NOTIFICATION_RESPONSE = 'ok';
    const INVALID_RESPONSE      = 'Error data';
    const INVALID_JSON_RESPONSE = '{"param":"value"}';
    const HTTP_CODE_SUCCESS     = 200;
    const HTTP_CODE_ERROR       = 500;


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
     * @return \PHPUnit_Framework_MockObject_MockBuilder
     */
    protected function prepareTransportMock()
    {
        return $this->getMockBuilder('\JsonRpc\Transport\PhpTransport')
            ->setConstructorArgs([[$this, 'transportCallback']]);
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
     * @param int    $code
     * @param string $response
     * @return \JsonRpc\Transport\PhpTransport
     */
    protected function getTransportMockSendRequest($code, $response)
    {
        $mock = $this->prepareTransportMock()
            ->setMethods(['sendRequest'])
            ->getMock();

        $mock->expects($this->any())
            ->method('sendRequest')
            ->willReturn([
                'http_code' => $code,
                'response'  => $response,
            ]);

        return $mock;
    }

    /**
     * @param int    $id
     * @param string $method
     * @param array  $data
     * @param bool   $named
     * @param bool   $notification
     * @return string
     */
    protected function generateRequest($id, $method, $data, $named = true, $notification = true)
    {
        $params = json_encode($named ? $data : array_values($data));
        $id = $notification ? '' : ",\"id\":{$id}";
        return "{\"jsonrpc\":\"2.0\",\"method\":\"{$method}\",\"params\":{$params}{$id}}";
    }

    /**
     * @param int   $id
     * @param mixed $data
     * @return string
     */
    protected function generateCorrectResponse($id, $data)
    {
        $result = json_encode($data);
        return "{\"jsonrpc\":\"2.0\",\"result\":{$result},\"id\":{$id}}";
    }

    /**
     * @param int    $id
     * @param int    $code
     * @param string $message
     * @param null   $data
     * @return string
     */
    protected function generateErrorResponse($id, $code, $message, $data = null)
    {
        $data = $data !== null ? ',"data":' . json_encode($data) : '';
        return "{\"jsonrpc\":\"2.0\",\"error\":{\"code\":{$code},\"message\":\"{$message}\"{$data}},\"id\":{$id}}";
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
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
     *
     */
    public function testConstructorCall()
    {
        $client = new Client(new PhpTransport);

        $this->assertAttributeInstanceOf('\JsonRpc\Transport\PhpTransport', '_transport', $client);
        $this->assertAttributeNotEmpty('headers', $client);
        $this->assertAttributeEquals(['Content-Type' => self::CONTENT_TYPE], 'headers', $client);
    }

    /**
     *
     */
    public function testCallNamed()
    {
        $id = 0;

        $response = $this->generateCorrectResponse($id, $this->data);
        $request = $this->generateRequest($id, self::CALL_METHOD, $this->data, true, false);

        $transport = $this->getTransportMockSendRequest(self::HTTP_CODE_SUCCESS, $response);
        $client = new Client($transport);
        $result = $client->call(self::CALL_METHOD, $this->data);

        $this->assertEquals("[request]{$request}[response]HTTP_CODE:200{$response}", $this->log);
        $this->assertEquals($this->data, $result);
    }

    /**
     *
     */
    public function testCallNotNamed()
    {
        $id = 1;

        $response = $this->generateCorrectResponse($id, $this->data);
        $request = $this->generateRequest($id, self::CALL_METHOD, $this->data, false, false);

        $transport = $this->getTransportMockSendRequest(self::HTTP_CODE_SUCCESS, $response);
        $client = new Client($transport);
        $result = $client->call(self::CALL_METHOD, $this->data, false);

        $this->assertEquals("[request]{$request}[response]HTTP_CODE:200{$response}", $this->log);
        $this->assertEquals($this->data, $result);
    }

    /**
     *
     */
    public function testNotifyNamed()
    {
        $id = 2;

        $response = self::NOTIFICATION_RESPONSE;
        $request = $this->generateRequest($id, self::CALL_METHOD, $this->data, true, true);

        $transport = $this->getTransportMockSendRequest(self::HTTP_CODE_SUCCESS, $response);
        $client = new Client($transport);
        $result = $client->notify(self::CALL_METHOD, $this->data);

        $this->assertEquals("[request]{$request}[response]HTTP_CODE:200{$response}", $this->log);
        $this->assertTrue($result);
    }

    /**
     *
     */
    public function testNotifyNotNamed()
    {
        $id = 3;

        $response = self::NOTIFICATION_RESPONSE;
        $request = $this->generateRequest($id, self::CALL_METHOD, $this->data, false, true);

        $transport = $this->getTransportMockSendRequest(self::HTTP_CODE_SUCCESS, $response);
        $client = new Client($transport);
        $result = $client->notify(self::CALL_METHOD, $this->data, false);

        $this->assertEquals("[request]{$request}[response]HTTP_CODE:200{$response}", $this->log);
        $this->assertTrue($result);
    }

    /**
     * @expectedException \JsonRpc\Transport\TransportException
     * @expectedExceptionCode -32700
     * @expectedExceptionMessage Parse error.
     */
    public function testInvalidResponse()
    {
        $transport = $this->getTransportMockSendRequest(self::HTTP_CODE_ERROR, self::INVALID_RESPONSE);
        $client = new Client($transport);
        $client->call(self::CALL_METHOD, []);
    }

    /**
     * @expectedException \JsonRpc\ClientException
     * @expectedExceptionCode -32700
     * @expectedExceptionMessage Parse error.
     */
    public function testServerParseError()
    {
        $id = 3;

        $transport = $this->getTransportMockSendRequest(
            self::HTTP_CODE_ERROR,
            $this->generateErrorResponse(
                $id,
                ClientException::CODE_PARSE_ERROR,
                ClientException::getErrorMessage(ClientException::CODE_PARSE_ERROR)
            )
        );
        $client = new Client($transport);
        $client->call(self::CALL_METHOD, []);
    }

    /**
     * @expectedException \JsonRpc\ClientException
     * @expectedExceptionCode -32600
     * @expectedExceptionMessage Invalid Request.
     */
    public function testServerInvalidRequest()
    {
        $id = 4;

        $transport = $this->getTransportMockSendRequest(
            self::HTTP_CODE_ERROR,
            $this->generateErrorResponse(
                $id,
                ClientException::CODE_INVALID_REQUEST,
                ClientException::getErrorMessage(ClientException::CODE_INVALID_REQUEST)
            )
        );
        $client = new Client($transport);
        $client->call(self::CALL_METHOD, []);
    }

    /**
     * @expectedException \JsonRpc\ClientException
     * @expectedExceptionCode -32601
     * @expectedExceptionMessage Method not found.
     */
    public function testServerNotFoundMethod()
    {
        $id = 5;

        $transport = $this->getTransportMockSendRequest(
            self::HTTP_CODE_ERROR,
            $this->generateErrorResponse(
                $id,
                ClientException::CODE_METHOD_NOT_FOUND,
                ClientException::getErrorMessage(ClientException::CODE_METHOD_NOT_FOUND)
            )
        );
        $client = new Client($transport);
        $client->call(self::CALL_METHOD, []);
    }

    /**
     * @expectedException \JsonRpc\ClientException
     * @expectedExceptionCode -32602
     * @expectedExceptionMessage Invalid params.
     */
    public function testServerInvalidParams()
    {
        $id = 6;

        $transport = $this->getTransportMockSendRequest(
            self::HTTP_CODE_ERROR,
            $this->generateErrorResponse(
                $id,
                ClientException::CODE_INVALID_PARAMS,
                ClientException::getErrorMessage(ClientException::CODE_INVALID_PARAMS)
            )
        );
        $client = new Client($transport);
        $client->call(self::CALL_METHOD, []);
    }

    /**
     * @expectedException \JsonRpc\ClientException
     * @expectedExceptionCode -32603
     * @expectedExceptionMessage Internal error.
     */
    public function testServerInternalError()
    {
        $id = 7;

        $transport = $this->getTransportMockSendRequest(
            self::HTTP_CODE_ERROR,
            $this->generateErrorResponse(
                $id,
                ClientException::CODE_INTERNAL_ERROR,
                ClientException::getErrorMessage(ClientException::CODE_INTERNAL_ERROR)
            )
        );
        $client = new Client($transport);
        $client->call(self::CALL_METHOD, []);
    }

    /**
     * @expectedException \JsonRpc\ClientException
     * @expectedExceptionCode -32000
     * @expectedExceptionMessage Server error.
     */
    public function testServerError()
    {
        $id = 8;

        $transport = $this->getTransportMockSendRequest(
            self::HTTP_CODE_ERROR,
            $this->generateErrorResponse(
                $id,
                ClientException::CODE_SERVER_ERROR,
                ClientException::getErrorMessage(ClientException::CODE_SERVER_ERROR),
                [1, 2, 3]
            )
        );
        $client = new Client($transport);
        $client->call(self::CALL_METHOD, []);
    }

    /**
     * @expectedException \JsonRpc\ClientException
     * @expectedExceptionCode 0
     * @expectedExceptionMessage Unknown error
     */
    public function testUnknownError()
    {
        $id = 9;

        $transport = $this->getTransportMockSendRequest(
            self::HTTP_CODE_ERROR,
            "{\"jsonrpc\":\"2.0\",\"error\":{},\"id\":{$id}}"
        );
        $client = new Client($transport);
        $client->call(self::CALL_METHOD, []);
    }
}
