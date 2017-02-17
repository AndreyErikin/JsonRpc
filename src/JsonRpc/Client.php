<?php
namespace JsonRpc;

use JsonRpc\Transport\Transport;

/**
 * Class Client
 *
 * @package JsonRpc
 */
class Client
{
    const JSONRPC_VERSION = '2.0';

    const CONTENT_TYPE_DEFAULT   = 'application/json-rpc';
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
            'params'  => $namedParameters ? (object)$arguments : array_values($arguments),
            'id'      => $id,
        ];

        $response = $this->_transport->send(false, $request, $this->url, $this->headers);

        // simplify conditions, yeah, got duplicate code, but now we can read this
        if (!isset($response['jsonrpc'])) {
            throw new ClientException(ClientException::CODE_INVALID_RESPONSE);
        }
        if ($response['jsonrpc'] !== self::JSONRPC_VERSION) {
            throw new ClientException(ClientException::CODE_INVALID_RESPONSE);
        }
        if (!isset($response['id'])) {
            throw new ClientException(ClientException::CODE_INVALID_RESPONSE);
        }
        if ($response['id'] !== $id) {
            throw new ClientException(ClientException::CODE_INVALID_RESPONSE);
        }
        if (!array_key_exists('result', $response) && !isset($response['error'])) {
            throw new ClientException(ClientException::CODE_INVALID_RESPONSE);
        }


        if (isset($response['error'])) {
            $errorCode = ClientException::CODE_UNKNOWN_ERROR;
            $errorMessage = $errorData = null;
            if (isset($response['error']['code'])) {
                $errorCode = $response['error']['code'];
            }
            if (isset($response['error']['message'])) {
                $errorMessage = $response['error']['message'];
            }
            if (isset($response['error']['data'])) {
                $errorData = $response['error']['data'];
            }
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
            'params'  => $namedParameters ? (object)$arguments : array_values($arguments),
        ];

        return $this->_transport->send(true, $request, $this->url, $this->headers);
    }

}