<?php
namespace JsonRpc\Transport;

use Exception;
use JsonRpc\Base\JsonRpcException;

/**
 * Class PhpTransport
 *
 * @package JsonRpc\Transport
 */
class PhpTransport extends Transport
{
    /**
     * Send request.
     *
     * @param bool   $isNotification
     * @param array  $data
     * @param string $url
     * @param array  $headers
     *
     * @return bool|mixed
     * @throws \JsonRpc\Transport\TransportException
     */
    public function send($isNotification, array $data, $url, array $headers = [])
    {
        $this->request = json_encode($data);
        $errorCode = json_last_error();
        if ($errorCode !== JSON_ERROR_NONE) {

            throw new TransportException(
                JsonRpcException::CODE_INTERNAL_ERROR,
                null,
                null,
                new Exception(json_last_error_msg(), $errorCode)
            );
        }

        $logMessage = null;

        if ($this->_log
            || $this->_profile
        ) {
            $logMessage = "{$url}{$this->logBlockDelimiter}";

            if ($this->addHeadersToLog) {
                $logMessage .= implode($this->logItemsDelimiter, $headers) . $this->logBlockDelimiter;
            }

            $logMessage .= $this->request;
        }

        if ($this->_log) {
            call_user_func($this->_log, self::LOG_REQUEST, $logMessage);
        }

        if ($this->_profile) {
            call_user_func($this->_profile, self::PROFILE_BEGIN, $logMessage);
        }

        $this->response = $this->sendRequest(
            $isNotification,
            $this->request,
            $url,
            $headers
        );

        if ($this->_profile) {
            call_user_func($this->_profile, self::PROFILE_END, $logMessage);
        }

        if ($this->_log) {
            $logMessage = "HTTP_CODE:{$this->response['http_code']}{$this->logBlockDelimiter}{$this->response['response']}";

            call_user_func($this->_log, self::LOG_RESPONSE, $logMessage);
        }

        if ($isNotification) {

            return $this->response['http_code'] == 200
                ? true
                : false;
        }

        $result = json_decode($this->response['response'], true);
        $errorCode = json_last_error();
        if ($errorCode !== JSON_ERROR_NONE) {

            throw new TransportException(
                JsonRpcException::CODE_PARSE_ERROR,
                null,
                null,
                new Exception(json_last_error_msg(), $errorCode)
            );
        }

        return $result;
    }

    /**
     * Return request data.
     *
     * @return mixed
     * @throws \JsonRpc\Transport\TransportException
     */
    public function receive()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            throw new TransportException(JsonRpcException::CODE_INVALID_REQUEST);
        }

        $this->request = $this->getRequest();

        if ($this->_log) {
            $logMessage = $_SERVER['PHP_SELF'] . $_SERVER['QUERY_STRING'] . $this->logBlockDelimiter;

            if ($this->addHeadersToLog) {
                $headers = array_map(
                    function ($key, $value) {
                        return $key . ': ' . $value;
                    },
                    array_keys(getallheaders()), getallheaders()
                );

                $logMessage .= implode($this->logItemsDelimiter, $headers) . $this->logBlockDelimiter;
            }

            $logMessage .= $this->request;

            call_user_func($this->_log, self::LOG_REQUEST, $logMessage);
        }

        if (!$this->request) {

            throw new TransportException(JsonRpcException::CODE_INVALID_REQUEST);
        }

        $data = json_decode($this->request, true);
        if (json_last_error() !== JSON_ERROR_NONE) {

            throw new TransportException(JsonRpcException::CODE_PARSE_ERROR);
        }

        return $data;
    }

    /**
     * Send response.
     *
     * @param array|null   $data
     * @param null|integer $errorCode
     * @param array        $headers
     *
     * @throws \JsonRpc\Transport\TransportException
     */
    public function respond($data, $errorCode = null, array $headers = [])
    {
        if (empty($data)) {
            $this->sendResponse(null, $errorCode, $headers);
        }

        $this->response = json_encode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {

            throw new TransportException(JsonRpcException::CODE_INTERNAL_ERROR);
        }

        if ($this->_log) {
            $logMessage = '';

            if ($this->addHeadersToLog) {
                $logMessage .= implode($this->logItemsDelimiter, $headers) . $this->logBlockDelimiter;
            }

            $logMessage .= $this->response;

            call_user_func($this->_log, self::LOG_RESPONSE, $logMessage);
        }

        $this->sendResponse($this->response, $errorCode, $headers);
    }

    /**
     * Send request using curl.
     *
     * @param bool   $isNotification
     * @param string $data
     * @param string $url
     * @param array  $headers
     *
     * @return array|bool
     * @throws \JsonRpc\Transport\TransportException
     */
    protected function sendRequest($isNotification, $data, $url, array $headers)
    {
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => $isNotification ? 0 : 1,
            CURLOPT_CONNECTTIMEOUT => $this->_connectionTimeout,
        ];

        if ($this->_executionTimeout) {
            $options[CURLOPT_TIMEOUT] = $this->_executionTimeout;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        $errorCode = curl_errno($ch);

        if ($errorCode !== CURLE_OK) {

            throw new TransportException(
                JsonRpcException::CODE_INTERNAL_ERROR,
                null,
                null,
                new Exception(curl_error($ch), $errorCode)
            );
        }

        $info = curl_getinfo($ch);
        curl_close($ch);

        return [
            'http_code' => $info['http_code'],
            'response'  => $response,
        ];
    }

    /**
     * @return string
     * @throws \JsonRpc\Transport\TransportException
     */
    protected function getRequest()
    {
        return file_get_contents('php://input');
    }

    /**
     * @param string       $data
     * @param integer|null $errorCode
     * @param array        $headers
     */
    protected function sendResponse($data, $errorCode, array $headers = [])
    {
        switch ($errorCode) {
            case null:
                header('HTTP/1.1 200 OK');
                break;
            case JsonRpcException::CODE_INVALID_REQUEST:
                header('HTTP/1.1 400 Bad Request');
                break;
            case JsonRpcException::CODE_METHOD_NOT_FOUND:
                header('HTTP/1.1 404 Not Found');
                break;
            case JsonRpcException::CODE_INTERNAL_ERROR:
            case JsonRpcException::CODE_PARSE_ERROR:
            case JsonRpcException::CODE_INVALID_PARAMS:
            default:
                header('HTTP/1.1 500 Internal error');
        }

        header('Connection: close');
        header('Content-Length: ' . strlen($data));

        foreach ($headers as $header) {
            header($header);
        }

        echo $data;

        exit;
    }
}