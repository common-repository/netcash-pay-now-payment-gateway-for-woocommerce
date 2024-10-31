<?php
namespace Netcash\PayNow\Exceptions;

class ValidationException extends \Exception
{
    public function __construct($field, $reason)
    {
        $message = "Field {$field} is invalid. {$reason}.";
        parent::__construct($message, 0, null);
    }

    // custom string representation of object
//    public function __toString()
//    {
//        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
//    }
}
