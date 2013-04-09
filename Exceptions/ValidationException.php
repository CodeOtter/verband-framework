<?php

namespace Verband\Framework\Exceptions;

/**
 * Define a custom exception class
 */
use Verband\Framework\Util\Validator;

class ValidationException extends \Exception
{
	
	
    // Redefine the exception so message isn't optional
    public function __construct($errorList) {
    	$body = '';
    	foreach($errorList as $entity => $fields) {
    		foreach($fields as $field => $codes) {
    			foreach($codes as $code) {
    				$body .= $entity . '\\' . $field . ': ' . Validator::getErrorMessage($code). "\n";
    			}
    		}
    	}
        parent::__construct('A form failed: ' . $body, -1);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}