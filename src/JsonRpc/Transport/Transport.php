<?php
namespace JsonRpc\Transport;

/**
 * Class Transport
 *
 * @package JsonRpc\Transport
 */
abstract class Transport
{
    const LOG_REQUEST  = 'request';
    const LOG_RESPONSE = 'response';

    const PROFILE_BEGIN = 'begin';
    const PROFILE_END   = 'end';

    // not limit
    const CONNECTION_TIMEOUT_DEFAULT = 0;
    // not limit
    const EXECUTION_TIMEOUT_DEFAULT = 0;

    /** @var string */
    public $request;
    /** @var string */
    public $response;
    /** @var string */
    public $logItemsDelimiter = "\n";
    /** @var string */
    public $logBlockDelimiter = "\n";
    /** @var bool */
    public $addHeadersToLog = false;

    /** @var callable|null */
    protected $_log;
    /** @var callable|null */
    protected $_profile;
    /** @var int */
    protected $_executionTimeout = self::EXECUTION_TIMEOUT_DEFAULT;
    /** @var int */
    protected $_connectionTimeout = self::CONNECTION_TIMEOUT_DEFAULT;

    /**
     * @param callable|null $logCallback
     * @param callable|null $profileCallback
     */
    public function __construct($logCallback = null, $profileCallback = null)
    {
        $this->setLog($logCallback);
        $this->setProfile($profileCallback);
    }

    /**
     * Set log callback function.
     *
     * @param callable|null $callee
     */
    public function setLog($callee = null)
    {
        $this->_log = $callee && is_callable($callee, true) ? $callee : null;
    }

    /**
     * Set profile callback function.
     *
     * @param callable|null $callee
     */
    public function setProfile($callee = null)
    {
        $this->_profile = $callee && is_callable($callee, true) ? $callee : null;
    }

    /**
     * Set connection timeout.
     *
     * @param int $seconds (default not limit = 0)
     */
    public function setConnectionTimeout($seconds = self::CONNECTION_TIMEOUT_DEFAULT)
    {
        $this->_connectionTimeout = intval($seconds);
    }

    /**
     * Set execution timeout.
     *
     * @param int $seconds (default not limit = 0)
     */
    public function setExecutionTimeout($seconds = self::EXECUTION_TIMEOUT_DEFAULT)
    {
        $this->_executionTimeout = intval($seconds);
    }

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
    abstract public function send($isNotification, array $data, $url, array $headers = []);

    /**
     * Return request data.
     *
     * @return mixed
     * @throws \JsonRpc\Transport\TransportException
     */
    abstract public function receive();

    /**
     * Send response.
     *
     * @param array|null   $data
     * @param null|integer $errorCode
     * @param array        $headers
     *
     * @throws \JsonRpc\Transport\TransportException
     */
    abstract public function respond($data, $errorCode = null, array $headers = []);
}
