<?php
namespace JsonRpc\Test;

use JsonRpc\ClientException;

/**
 * Class ClientExceptionTest
 *
 * @package JsonRpc\Test
 */
class ClientExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test instance of
     */
    public function testExceptionInstance()
    {
        $this->assertInstanceOf('\JsonRpc\Base\JsonRpcException', new ClientException());
    }

    /**
     * Test static method with error messages
     */
    public function testGetErrorMessage()
    {
        $this->assertEquals('Invalid Response.', ClientException::getErrorMessage(ClientException::CODE_INVALID_RESPONSE));
    }
}
