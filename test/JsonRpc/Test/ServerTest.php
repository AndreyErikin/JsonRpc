<?php
namespace JsonRpc\Test;

use JsonRpc\Fixtures\Base\TestCalleeObject;
use JsonRpc\Server;
use JsonRpc\Transport\PhpTransport;

/**
 * Class Server
 * @package JsonRpc\Test
 */
class ServerTest extends \PHPUnit_Framework_TestCase
{
    const CONTENT_TYPE           = 'Content-Type: application/json-rpc; charset=utf-8';
    const CONTENT_TYPE_CORRECT   = 'application/json-rpc; charset=utf-8';
    const CONTENT_TYPE_ERROR     = 'error';
    const REQUEST_METHOD_CORRECT = 'POST';
    const REQUEST_METHOD_ERROR   = 'GET';
    const QUERY_STRING           = '';
    const SERVER_PHP_SELF        = '';

    const REQUEST_CALL_TEST  = '{"jsonrpc":"2.0", "method":"test", "params":[], "id":"1"}';
    const RESPONSE_CALL_TEST = '{"jsonrpc":"2.0","result":true,"id":"1"}';

    const REQUEST_CALL_TEST_INPUT  = '{"jsonrpc":"2.0", "method":"test_input", "params":{"input":{"param":"value"}}, "id":"2"}';
    const RESPONSE_CALL_TEST_INPUT = '{"jsonrpc":"2.0","result":{"param":"value"},"id":"2"}';

    const REQUEST_CALL_NOT_FOUND_METHOD  = '{"jsonrpc":"2.0", "method":"not_found_method", "params":[], "id":"3"}';
    const RESPONSE_CALL_NOT_FOUND_METHOD = '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found."},"id":"3"}';

    const REQUEST_ERROR  = 'Error data';
    const RESPONSE_ERROR = '{"jsonrpc":"2.0","error":{"code":-32700,"message":"Parse error."},"id":null}';

    const REQUEST_JSON_ERROR  = '{"param":"value"}';
    const RESPONSE_JSON_ERROR = '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request."},"id":null}';

    /**
     * @var string
     */
    protected $log = '';

    /**
     * @param string $return
     * @return \JsonRpc\Fixtures\Transport\TestPhpTransport
     */
    protected function getTransportMockServerRun($return)
    {
        $mock = $this->getMockBuilder('\JsonRpc\Fixtures\Transport\TestPhpTransport')
            ->setConstructorArgs([[$this, 'transportCallback']])
            ->setMethods(['getRequest'])
            ->getMock();

        $mock->expects($this->any())
            ->method('getRequest')
            ->willReturn($return);

        return $mock;
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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        // set correct headers
        $_SERVER['REQUEST_METHOD'] = self::REQUEST_METHOD_CORRECT;
        $_SERVER['CONTENT_TYPE'] = self::CONTENT_TYPE_CORRECT;
        $_SERVER['PHP_SELF'] = self::SERVER_PHP_SELF;
        $_SERVER['QUERY_STRING'] = self::QUERY_STRING;

        $this->log = '';
    }

    /**
     *
     */
    public function testConstructorCall()
    {
        $server = new Server(new PhpTransport, new TestCalleeObject);

        // check private attributes
        $this->assertAttributeInstanceOf('\JsonRpc\Transport\PhpTransport', '_transport', $server);
        $this->assertAttributeInstanceOf('\JsonRpc\Base\CalleeObject', '_callee', $server);
        $this->assertAttributeEquals(null, '_id', $server);

        // check public attributes
        $this->assertFalse($server->displayErrors);
        $this->assertEquals(['Content-Type' => self::CONTENT_TYPE], $server->headers);
    }

    /**
     *
     */
    public function testRun()
    {
        $request = self::REQUEST_CALL_TEST;
        $response = self::RESPONSE_CALL_TEST;

        $server = new Server($this->getTransportMockServerRun($request), new TestCalleeObject);

        $this->expectOutputString("{$response}");
        $server->run();
        $this->assertEquals("[request]{$request}[response]{$response}", $this->log);
    }

    /**
     *
     */
    public function testRunInput()
    {
        $request = self::REQUEST_CALL_TEST_INPUT;
        $response = self::RESPONSE_CALL_TEST_INPUT;

        $server = new Server($this->getTransportMockServerRun($request), new TestCalleeObject);

        $this->expectOutputString("{$response}");
        $server->run();
        $this->assertEquals("[request]{$request}[response]{$response}", $this->log);
    }

    /**
     *
     */
    public function testRunNotFound()
    {
        $request = self::REQUEST_CALL_NOT_FOUND_METHOD;
        $response = self::RESPONSE_CALL_NOT_FOUND_METHOD;

        $server = new Server($this->getTransportMockServerRun($request), new TestCalleeObject);

        $this->expectOutputString("{$response}");
        $server->run();
        $this->assertEquals("[request]{$request}[response]{$response}", $this->log);
    }

    /**
     *
     */
    public function testRunInvalidRequest()
    {
        $request = self::REQUEST_ERROR;
        $response = self::RESPONSE_ERROR;

        $server = new Server($this->getTransportMockServerRun($request), new TestCalleeObject);

        $this->expectOutputString("{$response}");
        $server->run();
        $this->assertEquals("[request]{$request}[response]{$response}", $this->log);
    }

    /**
     *
     */
    public function testRunInvalidJSONRequest()
    {
        $request = self::REQUEST_JSON_ERROR;
        $response = self::RESPONSE_JSON_ERROR;

        $server = new Server($this->getTransportMockServerRun($request), new TestCalleeObject);

        $this->expectOutputString("{$response}");
        $server->run();
        $this->assertEquals("[request]{$request}[response]{$response}", $this->log);
    }
}
