<?php
namespace JsonRpc\Fixtures\Transport;

use JsonRpc\Transport\PhpTransport;

/**
 * Class TestPhpTransport
 * @package JsonRpc\Fixtures\Transport
 */
class TestPhpTransport extends PhpTransport
{
    /**
     * @var string
     */
    protected $returnRequest;

    /**
     * @param $return
     */
    public function setReturnRequest($return)
    {
        $this->returnRequest = $return;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequest()
    {
        return $this->returnRequest;
    }

    /**
     * {@inheritdoc}
     */
    protected function sendResponse($data, $errorCode, array $headers = [])
    {
        echo $data;
    }
}
