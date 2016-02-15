<?php
namespace JsonRpc\Base;

use Exception;

/**
 * Class JsonRpcException
 *
 * @package JsonRpc\Base\
 */
class JsonRpcException extends Exception
{
    const CODE_UNKNOWN_ERROR = 0;

    const CODE_PARSE_ERROR      = -32700;
    const CODE_INVALID_REQUEST  = -32600;
    const CODE_METHOD_NOT_FOUND = -32601;
    const CODE_INVALID_PARAMS   = -32602;
    const CODE_INTERNAL_ERROR   = -32603;

    const CODE_SERVER_ERROR     = -32000;
    const CODE_SERVER_ERROR_MIN = -32099;
    const CODE_SERVER_ERROR_MAX = -32000;

    /** @var mixed|null */
    protected $_data;

    /**
     * @param int             $code
     * @param string|null     $message
     * @param mixed|null      $data
     * @param \Exception|null $previous
     */
    public function __construct($code = self::CODE_UNKNOWN_ERROR, $message = null, $data = null, Exception $previous = null)
    {
        if ($message === null) {
            $message = self::getErrorMessage($code);
        }

        $this->_data = $data;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    protected static function getErrorMessages()
    {
        return [
            self::CODE_UNKNOWN_ERROR    => 'Unknown error.',
            self::CODE_PARSE_ERROR      => 'Parse error.',
            self::CODE_INVALID_REQUEST  => 'Invalid Request.',
            self::CODE_METHOD_NOT_FOUND => 'Method not found.',
            self::CODE_INVALID_PARAMS   => 'Invalid params.',
            self::CODE_INTERNAL_ERROR   => 'Internal error.',
            self::CODE_SERVER_ERROR     => 'Server error.',
        ];
    }

    /**
     * Return error message from error code.
     *
     * @param integer $errorCode
     *
     * @return string|null
     */
    public static function getErrorMessage($errorCode)
    {
        $errorMessages = static::getErrorMessages();

        if (isset($errorMessages[$errorCode])) {

            return $errorMessages[$errorCode];

        } elseif ($errorCode >= self::CODE_SERVER_ERROR_MIN
            && $errorCode <= self::CODE_SERVER_ERROR_MAX
        ) {

            return $errorMessages[self::CODE_SERVER_ERROR];
        }

        return null;
    }

    /**
     * Create exception from error.
     *
     * @param integer $type
     * @param string  $code
     * @param string  $message
     * @param string  $file
     * @param string  $line
     *
     * @return \JsonRpc\Base\JsonRpcException
     */
    public static function fromError($type, $code, $message, $file, $line)
    {
        $self = get_called_class();

        return new $self(
            self::CODE_INTERNAL_ERROR,
            null,
            [
                'type'    => $type,
                'code'    => $code,
                'message' => $message,
                'file'    => $file,
                'line'    => $line,
            ]
        );
    }

    /**
     * Return exception data.
     *
     * @return mixed|null
     */
    public function getData()
    {
        return $this->_data;
    }
}