<?php
namespace JsonRpc\Base;

/**
 * Class CalleeObject
 *
 * @package JsonRpc\Base
 */
abstract class CalleeObject
{
    /**
     * Get rpc call method.
     *
     * @param $method
     *
     * @return array|string
     */
    public function getInvocationMethod($method)
    {
        return [$this, $method];
    }
}
