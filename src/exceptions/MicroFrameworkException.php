<?php

namespace it\hce\microframework\core\exceptions;


use Exception;

class MicroFrameworkException extends Exception
{

    public function __toString()
    {
        return $this->message;
    }
}