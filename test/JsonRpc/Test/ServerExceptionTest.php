<?php
namespace JsonRpc\Test;

use JsonRpc\ServerException;

/**
 * Class ServerExceptionTest
 *
 * @package JsonRpc\Test
 */
class ServerExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test instance of
     */
    public function testExceptionInstance()
    {
        $this->assertInstanceOf('\JsonRpc\Base\JsonRpcException', new ServerException());
    }
}
