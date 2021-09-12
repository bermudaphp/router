<?php

namespace Bermuda\Router\Exception;

class RouterException extends \RuntimeException
{
    /**
     * @throws static
     */
    public function throw() : void
    {
        throw $this;
    }
}
