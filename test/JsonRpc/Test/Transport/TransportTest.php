<?php

namespace JsonRpc\Test\Transport;

use JsonRpc\Transport\Transport;

/**
 * Class TransportTest
 * @package JsonRpc\Test\Transport
 */
class TransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $action
     * @param string $message
     */
    public function transportCallback($action, $message)
    {
    }

    /**
     *
     */
    public function testConstructorCall()
    {
        /** @var Transport $mock */
        $mock = $this->getMockForAbstractClass('JsonRpc\Transport\Transport');

        // asserting private properties
        $this->assertAttributeEquals(null, '_log', $mock);
        $this->assertAttributeEquals(null, '_profile', $mock);
        $this->assertAttributeEquals(Transport::CONNECTION_TIMEOUT_DEFAULT, '_connectionTimeout', $mock);
        $this->assertAttributeEquals(Transport::EXECUTION_TIMEOUT_DEFAULT, '_executionTimeout', $mock);

        // asserting public properties
        $this->assertAttributeEquals('', 'request', $mock);
        $this->assertAttributeEquals('', 'response', $mock);
        $this->assertAttributeEquals("\n", 'logItemsDelimiter', $mock);
        $this->assertAttributeEquals("\n", 'logBlockDelimiter', $mock);
        $this->assertAttributeEquals(false, 'addHeadersToLog', $mock);
    }

    /**
     *
     */
    public function testConstructorCallsInternalMethods()
    {
        $mock = $this->getMockBuilder('JsonRpc\Transport\Transport')
            ->disableOriginalConstructor()
            ->setMethods([
                'setLog',
                'setProfile',
                'setConnectionTimeout',
                'setExecutionTimeout',
            ])
            ->getMockForAbstractClass();

        $mock->expects($this->once())
            ->method('setLog')
            ->with($this->equalTo([$this, 'transportCallback']));

        $mock->expects($this->once())
            ->method('setProfile')
            ->with($this->equalTo([$this, 'transportCallback']));

        $reflectedClass = new \ReflectionClass('JsonRpc\Transport\Transport');
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($mock, [$this, 'transportCallback'], [$this, 'transportCallback']);
    }

    /**
     *
     */
    public function testSetLog()
    {
        /** @var Transport $mock */
        $mock = $this->getMockForAbstractClass('JsonRpc\Transport\Transport');

        $mock->setLog();
        $this->assertAttributeEquals(null, '_log', $mock);

        $mock->setLog([$this, 'transportCallback']);
        $this->assertAttributeEquals([$this, 'transportCallback'], '_log', $mock);
    }

    /**
     *
     */
    public function testSetProfile()
    {
        /** @var Transport $mock */
        $mock = $this->getMockForAbstractClass('JsonRpc\Transport\Transport');

        $mock->setProfile();
        $this->assertAttributeEquals(null, '_profile', $mock);

        $mock->setProfile([$this, 'transportCallback']);
        $this->assertAttributeEquals([$this, 'transportCallback'], '_profile', $mock);
    }

    /**
     *
     */
    public function testSetConnectionTimeout()
    {
        /** @var Transport $mock */
        $mock = $this->getMockForAbstractClass('JsonRpc\Transport\Transport');

        $mock->setConnectionTimeout(null);
        $this->assertAttributeEquals(0, '_connectionTimeout', $mock);

        $mock->setConnectionTimeout(1);
        $this->assertAttributeEquals(1, '_connectionTimeout', $mock);

        $mock->setConnectionTimeout('5');
        $this->assertAttributeEquals(5, '_connectionTimeout', $mock);

        $mock->setConnectionTimeout('some string');
        $this->assertAttributeEquals(0, '_connectionTimeout', $mock);

        $mock->setConnectionTimeout([]);
        $this->assertAttributeEquals(0, '_connectionTimeout', $mock);
    }

    /**
     *
     */
    public function testSetExecutionTimeout()
    {
        /** @var Transport $mock */
        $mock = $this->getMockForAbstractClass('JsonRpc\Transport\Transport');

        $mock->setExecutionTimeout(null);
        $this->assertAttributeEquals(0, '_executionTimeout', $mock);

        $mock->setExecutionTimeout(1);
        $this->assertAttributeEquals(1, '_executionTimeout', $mock);

        $mock->setExecutionTimeout('5');
        $this->assertAttributeEquals(5, '_executionTimeout', $mock);

        $mock->setExecutionTimeout('some string');
        $this->assertAttributeEquals(0, '_executionTimeout', $mock);

        $mock->setExecutionTimeout([]);
        $this->assertAttributeEquals(0, '_executionTimeout', $mock);
    }
}
