<?php
namespace JsonRpc;

use JsonRpc\Base\JsonRpcException;
use JsonRpc\Transport\Transport;

/**
 * Class ClientException
 *
 * @package JsonRpc
 */
class ClientException extends JsonRpcException
{
    const CODE_INVALID_RESPONSE = -1;

    protected static function getErrorMessages()
    {
        $messages = parent::getErrorMessages();

        $messages[self::CODE_INVALID_RESPONSE] = 'Invalid Response.';

        return $messages;
    }
}

/**
 * Class Client
 *
 * @package JsonRpc
 */
class Client
{
    const JSONRPC_VERSION = '2.0';

    const CONTENT_TYPE_DEFAULT = 'application/json-rpc';
    const CONTENT_ENCODE_DEFAULT = 'utf-8';

    /** @var string */
    public $url;
    /** @var array */
    public $headers = [];

    /** @var \JsonRpc\Transport\Transport */
    private $_transport;

    /** @var int */
    private static $_id = 0;

    /**
     * @param \JsonRpc\Transport\Transport $transport
     */
    public function __construct(Transport $transport)
    {
        $this->_transport = $transport;

        $contentType = self::CONTENT_TYPE_DEFAULT;
        $contentEncode = self::CONTENT_ENCODE_DEFAULT;
        $this->headers['Content-Type'] = "Content-Type: {$contentType}; charset={$contentEncode}";
    }

    /**
     * @param \JsonRpc\Transport\Transport $transport
     */
    public function setTransport(Transport $transport)
    {
        $this->_transport = $transport;
    }

    /**
     * Send rpc call.
     *
     * @param string $method
     * @param array  $arguments
     * @param bool   $namedParameters
     *
     * @return mixed
     * @throws \JsonRpc\ClientException
     */
    public function call($method, array $arguments = [], $namedParameters = true)
    {
        $id = self::$_id++;

        $request = [
            'jsonrpc' => self::JSONRPC_VERSION,
            'method'  => $method,
            'params'  => $namedParameters
                ? (object)$arguments
                : array_values($arguments),
            'id'      => $id,
        ];

        $response = $this->_transport->send(false, $request, $this->url, $this->headers);

        if (!isset($response['jsonrpc'])
            || $response['jsonrpc'] !== self::JSONRPC_VERSION
            || !isset($response['id'])
            || $response['id'] !== $id
            || (!array_key_exists('result', $response)
                && !isset($response['error'])
            )
        ) {

            throw new ClientException(ClientException::CODE_INVALID_RESPONSE);
        }

        if (isset($response['error'])) {
            $errorCode = isset($response['error']['code'])
                ? $response['error']['code']
                : ClientException::CODE_UNKNOWN_ERROR;

            $errorMessage = isset($response['error']['message'])
                ? $response['error']['message']
                : null;

            $errorData = isset($response['error']['data'])
                ? $response['error']['data']
                : null;

            throw new ClientException($errorCode, $errorMessage, $errorData);

        }

        return $response['result'];
    }

    /**
     * Send notification.
     *
     * @param       $method
     * @param array $arguments
     * @param bool  $namedParameters
     *
     * @return bool|mixed
     */
    public function notify($method, array $arguments = [], $namedParameters = true)
    {
        $request = [
            'jsonrpc' => self::JSONRPC_VERSION,
            'method'  => $method,
            'params'  => $namedParameters
                ? (object)$arguments
                : array_values($arguments),
        ];

        return $this->_transport->send(true, $request, $this->url, $this->headers);
    }

}