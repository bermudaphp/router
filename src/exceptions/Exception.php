<?php


namespace Lobster\Routing\Exceptions;


/**
 * Class Exception
 * @package Lobster\Routing\Exceptions
 */
class Exception extends \RuntimeException
{
    /**
     * @throws static
     */
    public function throw() : void
    {
        throw $this;
    }
}