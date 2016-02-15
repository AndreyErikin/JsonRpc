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
     * @var string
     */
    protected $log;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->log = '';
    }

    /**
     * @param string $action
     * @param string $message
     */
    protected function transportCallback($action, $message)
    {
        $this->log .= str_replace("\n", "", "[{$action}]{$message}");
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
}
