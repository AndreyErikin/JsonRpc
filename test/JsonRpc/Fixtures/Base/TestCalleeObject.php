<?php
namespace JsonRpc\Fixtures\Base;

use JsonRpc\Base\CalleeObject;

/**
 * Class TestCalleeObject
 * @package JsonRpc\Fixtures\Base
 */
class TestCalleeObject extends CalleeObject
{
    /**
     * @return bool
     */
    public function test()
    {
        return true;
    }

    /**
     * @param array $input
     * @return array
     */
    public function test_input($input)
    {
        return $input;
    }
}
