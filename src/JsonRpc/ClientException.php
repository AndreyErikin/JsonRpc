<?php
namespace JsonRpc;

use JsonRpc\Base\JsonRpcException;

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