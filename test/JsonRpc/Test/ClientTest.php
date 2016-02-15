<?php

namespace JsonRpc\Test;

use JsonRpc\Client;

/**
 * Class ClientTest
 * @package JsonRpc\Test
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var string
     */
    protected $contentEncode;

    protected function setUp()
    {
        $this->contentType = Client::CONTENT_TYPE_DEFAULT;
        $this->contentEncode = Client::CONTENT_ENCODE_DEFAULT;
    }

    /**
     *
     */
    public function testConstructor()
    {
        /** @var \JsonRpc\Transport\Transport $stub */
        $stub = $this->getMockForAbstractClass('\JsonRpc\Transport\Transport');
        $client = new Client($stub);

        $this->assertAttributeInstanceOf('\JsonRpc\Transport\Transport', '_transport', $client);
        $this->assertAttributeNotEmpty('headers', $client);
        $this->assertAttributeEquals(['Content-Type' => "Content-Type: {$this->contentType}; charset={$this->contentEncode}"], 'headers', $client);

    }
}
