<?php

namespace JsonRpc\Test;

use \JsonRpc\Server;

/**
 * Class Server
 * @package JsonRpc\Test
 */
class ServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testConstructorCall()
    {
        /** @var \JsonRpc\Server $mock */
        $mock = $this->getMockBuilder('\JsonRpc\Server')
            ->setConstructorArgs([
                $this->getMockForAbstractClass('\JsonRpc\Transport\Transport'),
                $this->getMockForAbstractClass('\JsonRpc\Base\CalleeObject')
            ])
            ->getMock();

        $this->assertAttributeInstanceOf('\JsonRpc\Transport\Transport', '_transport', $mock);
        $this->assertAttributeInstanceOf('\JsonRpc\Base\CalleeObject', '_callee', $mock);
        $this->assertAttributeEquals(null, '_id', $mock);

        $this->assertFalse($mock->displayErrors);

        $headers = [
            'Content-Type' => 'Content-Type: ' . Server::CONTENT_TYPE_DEFAULT . '; charset=' . Server::CONTENT_ENCODE_DEFAULT
        ];
        $this->assertEquals($headers, $mock->headers);
    }
}
