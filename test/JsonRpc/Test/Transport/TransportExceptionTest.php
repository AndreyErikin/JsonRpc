<?php
namespace JsonRpc\Test\Transport;

use JsonRpc\Transport\TransportException;

/**
 * Class TransportExceptionTest
 *
 * @package JsonRpc\Test\Transport
 */
class TransportExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test instance of
     */
    public function testExceptionInstance()
    {
        $this->assertInstanceOf('\JsonRpc\Base\JsonRpcException', new TransportException());
    }
}
