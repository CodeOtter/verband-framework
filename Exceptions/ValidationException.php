<?php

namespace Verband\Framework\Exceptions;

/**
 * Define a custom exception class
 */
use Verband\Framework\Util\Validator;

class ValidationException extends \Exception
{
	private
	    $errors = array();
	
    /**
     * 
     * @param unknown_type $errors
     */
    public function __construct($errors) {
        $this->errors = $errors; 
        parent::__construct($this->getString(), -1);
    }

    /**
     * 
     * @return multitype:
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * (non-PHPdoc)
     * @see Exception::__toString()
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    /**
     * 
     * @return string
     */
    private function getString() {
        $body = '';
        foreach($this->errors as $entity => $fields) {
            foreach($fields as $field => $codes) {
                foreach($codes as $code) {
                    $body .= $entity . '\\' . $field . ': ' . Validator::getErrorMessage($code). "\n";
                }
            }
        }

        return 'An error has occured: ' . $body;
    }
}