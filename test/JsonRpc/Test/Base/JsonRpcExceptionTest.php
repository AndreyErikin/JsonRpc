<?php
namespace JsonRpc\Test\Base;

use JsonRpc\Base\JsonRpcException;

/**
 * Class JsonRpcExceptionTest
 * @package JsonRpc\Test\Base
 */
class JsonRpcExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test constructor
     */
    public function testEmptyConstructor()
    {
        $exception = new JsonRpcException();
        $this->assertEquals(JsonRpcException::CODE_UNKNOWN_ERROR, $exception->getCode());
        $this->assertEquals(JsonRpcException::getErrorMessage($exception->getCode()), $exception->getMessage());
        $this->assertNull($exception->getData());
        $this->assertNull($exception->getPrevious());
    }

    /**
     * Test static method with error messages
     */
    public function testGetErrorMessage()
    {
        $this->assertEquals('Unknown error.', JsonRpcException::getErrorMessage(JsonRpcException::CODE_UNKNOWN_ERROR));
        $this->assertEquals('Parse error.', JsonRpcException::getErrorMessage(JsonRpcException::CODE_PARSE_ERROR));
        $this->assertEquals('Invalid Request.',
            JsonRpcException::getErrorMessage(JsonRpcException::CODE_INVALID_REQUEST));
        $this->assertEquals('Method not found.',
            JsonRpcException::getErrorMessage(JsonRpcException::CODE_METHOD_NOT_FOUND));
        $this->assertEquals('Invalid params.',
            JsonRpcException::getErrorMessage(JsonRpcException::CODE_INVALID_PARAMS));
        $this->assertEquals('Internal error.',
            JsonRpcException::getErrorMessage(JsonRpcException::CODE_INTERNAL_ERROR));
        $this->assertEquals('Server error.', JsonRpcException::getErrorMessage(JsonRpcException::CODE_SERVER_ERROR));

        $serverCodeRandom = mt_rand(JsonRpcException::CODE_SERVER_ERROR_MIN, JsonRpcException::CODE_SERVER_ERROR_MAX);
        $this->assertEquals('Server error.', JsonRpcException::getErrorMessage($serverCodeRandom));
    }

    /**
     * Test static method with error messages
     */
    public function testFromError()
    {
        $expected = new \Exception(__METHOD__, JsonRpcException::CODE_UNKNOWN_ERROR);
        $data = [
            'type'    => E_ERROR,
            'code'    => $expected->getCode(),
            'message' => $expected->getMessage(),
            'file'    => $expected->getFile(),
            'line'    => $expected->getLine()
        ];
        $actual = call_user_func_array(['JsonRpc\Base\JsonRpcException', 'fromError'], $data);


        $this->assertEquals(JsonRpcException::CODE_INTERNAL_ERROR, $actual->getCode());
        $this->assertEquals(JsonRpcException::getErrorMessage($actual->getCode()), $actual->getMessage());
        $this->assertEquals($data, $actual->getData());
        $this->assertNull($actual->getPrevious());
    }
}