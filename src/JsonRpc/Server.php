<?php
namespace JsonRpc;

use Exception;
use Throwable;
use ReflectionFunction;
use ReflectionMethod;
use JsonRpc\Base\CalleeObject;
use JsonRpc\Base\JsonRpcException;
use JsonRpc\Transport\Transport;

/**
 * Class Server
 *
 * @package JsonRpc
 */
class Server
{
    const JSONRPC_VERSION = '2.0';

    const CONTENT_TYPE_DEFAULT = 'application/json-rpc';
    const CONTENT_ENCODE_DEFAULT = 'utf-8';

    /** @var bool */
    public $displayErrors = false;
    /** @var array */
    public $headers = [];
    /** @var null default: E_ALL | E_STRICT */
    public $errorTypes = null;

    /** @var \JsonRpc\Transport\Transport */
    private $_transport;
    /** @var \JsonRpc\Base\CalleeObject */
    private $_callee;
    /** @var integer|string|null */
    private $_id = null;

    /**
     * @param \JsonRpc\Transport\Transport $transport
     * @param \JsonRpc\Base\CalleeObject   $calleeObject
     */
    public function __construct(Transport $transport, CalleeObject $calleeObject)
    {
        $this->_transport = $transport;
        $this->_callee = $calleeObject;

        $contentType = self::CONTENT_TYPE_DEFAULT;
        $contentEncode = self::CONTENT_ENCODE_DEFAULT;
        $this->headers['Content-Type'] = "Content-Type: {$contentType}; charset={$contentEncode}";
    }

    /**
     * Run server.
     */
    public function run()
    {
        ob_start();

        if (!$this->errorTypes) {
            $this->errorTypes = E_ALL | E_STRICT;
        }

        ini_set('display_errors', false);
        set_error_handler([$this, 'errorHandler'], $this->errorTypes);
        register_shutdown_function([$this, 'shutdownHandler']);

        try {
            $this->doRun();
        } catch (Exception $exception) {
            $this->exceptionHandler($exception);
        } catch (Throwable $exception) {
            $this->exceptionHandler($exception);
        }
    }

    /**
     * Exception handler.
     *
     * @param \Exception|\Throwable $exception
     */
    public function exceptionHandler($exception)
    {
        if (!($exception instanceof JsonRpcException)) {
            $exception = $this->displayErrors
                ? ServerException::fromError(
                    E_ERROR,
                    $exception->getCode(),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                )
                : new ServerException(
                    JsonRpcException::CODE_INTERNAL_ERROR
                );
        }
        $this->respondError($exception);
    }

    /**
     * @param int    $code
     * @param string $message
     * @param string $file
     * @param int    $line
     *
     * @throws \JsonRpc\ServerException
     */
    public function errorHandler($code, $message, $file, $line)
    {
        $exception = $this->displayErrors
            ? ServerException::fromError(
                E_ERROR,
                $code,
                $message,
                $file,
                $line
            )
            : new ServerException(
                JsonRpcException::CODE_INTERNAL_ERROR
            );
        throw $exception;
    }

    /**
     * Shutdown handler.
     *
     * @throws \JsonRpc\ServerException
     */
    public function shutdownHandler()
    {
        $error = error_get_last();
        if ($error !== null) {
            $exception = $this->displayErrors
                ? ServerException::fromError(
                    $error['type'],
                    0,
                    $error['message'],
                    $error['file'],
                    $error['line']
                )
                : new ServerException(
                    JsonRpcException::CODE_INTERNAL_ERROR
                );
            throw $exception;
        }
    }

    /**
     * Run process server.
     *
     * @throws \JsonRpc\ServerException
     */
    protected function doRun()
    {
        $request = $this->_transport->receive();

        if (!self::isValidJsonRpc($request)) {
            throw $this->displayErrors
                ? new ServerException(
                    JsonRpcException::CODE_INVALID_REQUEST,
                    null,
                    $this->_transport->request
                )
                : new ServerException(
                    JsonRpcException::CODE_INVALID_REQUEST
                );
        }

        if (isset($request['id'])) {
            $this->_id = $request['id'];
            $isNotification = false;

        } else {
            $isNotification = true;
        }

        $method = $request['method'];
        $params = isset($request['params'])
            ? $request['params']
            : [];

        $result = $this->call($method, $params);

        $this->respond($isNotification, $result);
    }

    /**
     * @param array $request
     *
     * @return bool
     */
    protected static function isValidJsonRpc($request)
    {
        if (!isset($request['jsonrpc'])
            || $request['jsonrpc'] !== self::JSONRPC_VERSION
        ) {

            return false;
        }

        if (!isset($request['method'])
            || !is_string($request['method'])
            || preg_match('/^rpc\./', $request['method'])
        ) {

            return false;
        }

        if (array_key_exists('params', $request)
            && !is_array($request['params'])
        ) {

            return false;
        }

        if (array_key_exists('id', $request)
            && (!is_integer($request['id'])
                && !is_string($request['id'])
                && !is_null($request['id'])
            )
        ) {

            return false;
        }

        return true;
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return mixed
     * @throws \JsonRpc\ServerException
     */
    protected function call($method, array $params)
    {
        $invocation = $this->_callee->getInvocationMethod($method);
        if (!is_callable($invocation)) {

            throw $this->displayErrors
                ? new ServerException(
                    JsonRpcException::CODE_METHOD_NOT_FOUND,
                    null,
                    $method
                )
                : new ServerException(
                    JsonRpcException::CODE_METHOD_NOT_FOUND
                );
        }

        $callMethod = is_array($invocation)
            ? new ReflectionMethod($invocation[0], $invocation[1])
            : new ReflectionFunction($invocation);

        $callParams = [];
        $firsKey = key($params);

        if ($firsKey !== null) {
            foreach ($callMethod->getParameters() as $parameter) {
                if (array_key_exists($parameter->name, $params)) {
                    $callParams[] = $params[$parameter->name];
                    unset($params[$parameter->name]);

                } else {
                    $callParams[] = $parameter->isOptional()
                        ? $parameter->getDefaultValue()
                        : null;
                }
            }

            if (!empty($params)) {

                throw $this->displayErrors
                    ? new ServerException(
                        JsonRpcException::CODE_INVALID_PARAMS,
                        null,
                        $params
                    )
                    : new ServerException(
                        JsonRpcException::CODE_INVALID_PARAMS
                    );
            }
        }

        return call_user_func_array($invocation, $callParams);
    }

    /**
     * @param bool  $isNotification
     * @param mixed $result
     */
    protected function respond($isNotification, $result)
    {
        if ($isNotification) {
            $response = null;

        } else {
            $response = [
                'jsonrpc' => self::JSONRPC_VERSION,
                'result'  => $result,
                'id'      => $this->_id,
            ];
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        $this->_transport->respond($response, null, $this->headers);
    }

    /**
     * @param \JsonRpc\Base\JsonRpcException $exception
     */
    protected function respondError(JsonRpcException $exception)
    {
        $response = [
            'jsonrpc' => self::JSONRPC_VERSION,
            'error'   => [
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],
            'id'      => $this->_id,
        ];

        $data = $exception->getData();
        if ($data !== null) {
            $response['error']['data'] = $data;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        $this->_transport->respond(
            $response,
            $exception->getCode(),
            $this->headers
        );
    }

}
