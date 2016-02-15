<?php

namespace JsonRpc\Test\Base;

use JsonRpc\Base\CalleeObject;

/**
 * Class CalleeObjectTest
 * @package JsonRpc\Test\Base
 */
class CalleeObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test method equals
     */
    public function testGetInvocationMethodEquals()
    {
        $mock = $this->getMockForAbstractClass('\JsonRpc\Base\CalleeObject');
        $mock->expects($this->any())
            ->method('getInvocationMethod');
        /** @var $mock CalleeObject */
        $this->assertEquals([$mock, 'testMethod'], $mock->getInvocationMethod('testMethod'));
    }
}
