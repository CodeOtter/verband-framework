<?php 

namespace Verband\Framework\Util;

use Verband\Framework\Exceptions\ValidationException;

class Validator {

	protected static
		$errors;
	
	const
		ERROR_TOO_SMALL				= 1,
		ERROR_TOO_LARGE				= 2,
		ERROR_NO_MATCH				= 3,
		ERROR_NOT_EMAIL				= 4,
		ERROR_NOT_NUMBER			= 5,
		ERROR_NOT_STRING			= 6,
		ERROR_NOT_DATE				= 7,
		ERROR_NOT_ARRAY				= 8,
		ERROR_NOT_OBJECT			= 9,
		ERROR_NOT_CORRECT_INSTANCE	= 10,
		ERROR_NOT_EQUALS			= 11,
		ERROR_EQUALS				= 12,
		ERROR_NOT_NULL				= 13,
		ERROR_NULL					= 14,
		ERROR_INVALID_TYPE			= 15,
		ERROR_EMPTY					= 16,
		ERROR_NOT_EMPTY				= 17,
		ERROR_NOT_SET				= 18,
		ERROR_ALREADY_EXISTS		= 19,
		ERROR_UNEXPECTED_FORMAT		= 20,
		ERROR_EXCEPTION_THROWN      = 21;

	protected
		$entityName,
		$name,
		$value;

	public static function getErrorMessage($code) {
		switch($code) {
			case self::ERROR_TOO_SMALL				: return 'Minimum length required';
			case self::ERROR_TOO_LARGE				: return 'Maximum length exceeded';
			case self::ERROR_NO_MATCH				: return 'No match';
			case self::ERROR_NOT_EMAIL				: return 'Not an email address';
			case self::ERROR_NOT_NUMBER				: return 'Not a number';
			case self::ERROR_NOT_STRING				: return 'Not a string';
			case self::ERROR_NOT_DATE				: return 'Not a date';
			case self::ERROR_NOT_ARRAY				: return 'Not an array';
			case self::ERROR_NOT_OBJECT				: return 'Not an object';
			case self::ERROR_NOT_CORRECT_INSTANCE	: return 'Not the correct object instance';
			case self::ERROR_NOT_EQUALS				: return 'Unequal';
			case self::ERROR_EQUALS					: return 'Eqaul';
			case self::ERROR_NOT_NULL				: return 'Must be null';
			case self::ERROR_NULL					: return 'Can\'t be null';
			case self::ERROR_INVALID_TYPE			: return 'Not a valid type';
			case self::ERROR_EMPTY					: return 'Can\'t be empty';
			case self::ERROR_NOT_EMPTY				: return 'Must be empty';
			case self::ERROR_NOT_SET				: return 'Not set';
			case self::ERROR_ALREADY_EXISTS			: return 'Already exists';
			case self::ERROR_UNEXPECTED_FORMAT		: return 'Unexpected format';
			case self::ERROR_EXCEPTION_THROWN       : return 'Exception thrown';
			default									: return 'Unknown';
		}
	}
	
	/**
	 *
	 * Enter description here ...
	 */
	public function __construct($entityName) {
		if(self::$errors === null) {
			self::$errors = array();
		}
		$this->entityName = $entityName;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function on($name, $value) {
		$this->name = $name;
		$this->value = $value;
		return $this;
	}
	
	/**
	 *
	 * Enter description here ...
	 */
	public function isBetween($start, $end) {
		$this->isGreaterThan($start);
		$this->isLessThan($end);
		return $this;
	}

	/**
	 *
	 * Enter description here ...
	 */
	public function matches($pattern) {
		if(!preg_match($pattern, $this->value)) {
			$this->error(self::ERROR_NO_MATCH);
		}
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function isEmail() {
		if(!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
			$this->error(self::ERROR_NOT_EMAIL);
		}
		return $this;
	}

	/**
	 *
	 * Enter description here ...
	 */
	public function isNumeric() {
		if(!is_numeric($this->value)) {
			$this->error(self::ERROR_NOT_NUMBER);
		}	
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function isString() {
		if(!is_string($this->value)) {
			$this->error(self::ERROR_NOT_STRING);
		}
		return $this;
	}

	/**
	*
	* Enter description here ...
	*/
	public function isDate() {
		if(!($this->value instanceof \DateTime)) {
			$this->error(self::ERROR_NOT_DATE);
		}
		return $this;
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function isArray() {
		if(!is_array($this->value)) {
			$this->error(self::ERROR_NOT_ARRAY);
		}
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function isObject() {
		if(!is_object($this->value)) {
			$this->error(self::ERROR_NOT_OBJECT);
		}
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $className
	 */
	public function isInstanceOf($className) {
		if(!is_subclass_of($this->value, $className)) {
			$this->error(self::ERROR_NOT_CORRECT_INSTANCE);
		}
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $value
	 */
	public function isGreaterThan($value) {
		if(is_numeric($this->value)) {
			$compare = $this->value;
		} else if(is_string($this->value)) {
			$compare = strlen($this->value);
		} else if(is_array($this->value)) {
			$compare = count($this->value);
		} else if($this->value instanceof \DateTime) {
			$compare = $this->value->getTimestamp();
			$datetime = new \DateTime($value);
			$value = $datetime->getTimestamp();
		} else{
			$this->error(self::ERROR_INVALID_TYPE);
			return $this;
		}
		
		if($compare < $value) {
			$this->error(self::ERROR_TOO_SMALL);
		}

		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $value
	 */
	public function isLessThan($value) {
		if(is_numeric($value)) {
			$compare = $this->value;
		} else if(is_string($value)) {
			$compare = strlen($this->value);
		} else if(is_array($value)) {
			$compare = count($this->value);
		} else if($value instanceof \DateTime) {
			$compare = $this->value->getTimestamp();
			$datetime = new \DateTime($value);
			$value = $$datetime->getTimestamp();
		} else {
			$this->error(self::ERROR_INVALID_TYPE);
			return $this;
		}

		if($compare > $value) {
			$this->error(self::ERROR_TOO_LARGE);
		}

		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $value
	 */
	public function isEmpty() {
		if((is_string($this->value) && $this->value != '') || $this->value !== null || (is_array($this->value) && count($this->value) != 0)) {
			$this->error(self::ERROR_NOT_EMPTY);
		}
		return $this;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $value
	 */
	public function isNotEmpty() {
		if((is_string($this->value) && trim($this->value) == '') || $this->value === null || (is_array($this->value) && count($this->value) == 0)) {
			$this->error(self::ERROR_EMPTY);
		}
		return $this;
	}
	
	/**
	 * 
	 * @param unknown $value
	 * @return \Verband\Framework\Util\Validator
	 */
	public function is($value) {
		if(is_object($value)) {
			if($this->value !== $value) {
				$this->error(self::ERROR_NOT_EQUALS);
			}
		} else {
			if($this->value != $value) {
				$this->error(self::ERROR_NOT_EQUALS);
			}
		}
		return $this;
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $value
	 */
	public function isNot($value) {
		if(is_object($value)) {
			if($this->value === $value) {
				$this->error(self::ERROR_EQUALS);
			}
		} else {
			if($this->value == $value) {
				$this->error(self::ERROR_EQUALS);
			}	
		}
		
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function isNotNull() {
		if($this->value === null) {
			$this->error(self::ERROR_NULL);
		}
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function isNull() {
		if($this->value !== null) {
			$this->error(self::ERROR_NOT_NULL);
		}
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function trim() {
		$this->value = trim($this->value);
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function stripPattern($pattern) {
		$length = 0;
		while(strlen($this->value) != $length) {
			$length = strlen($this->value);
			$this->value = preg_replace($pattern, '', $this->value);
		}
		return $this;
	}

	/**
	 *
	 * Enter description here ...
	 */
	public function stripHtml() {
		$length = 0;
		while(strlen($this->value) != $length) {
			$length = strlen($this->value);
			$this->value = strip_tags($this->value);
		}
		return $this;
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function capitalize() {
		$this->value = strtoupper($this->value);
		return $this;
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function lowercase() {
		$this->value = strtolower($this->value);
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function asInteger() {
		$this->value = (integer)$this->value;
		return $this;
	}

	/**
	 *
	 * Enter description here ...
	 */
	public function asFloat() {
		$this->value = (float)$this->value;
		return $this;
	}
	
	/**
	 *
	 * Enter description here ...
	 */
	public function asBoolean() {
		$this->value = (boolean)$this->value;
		return $this;
	}
	
	/**
	 *
	 * Enter description here ...
	 */
	public function asString() {
		$this->value = (string)$this->value;
		return $this;
	}
	
	/**
	 *
	 * Enter description here ...
	 */
	public function asDatetime() {
		$this->value = new \DateTime($this->value);
		return $this;
	}

	/**
	*
	* Enter description here ...
	*/
	public function asTimestamp() {
		$this->value = new \DateTime('@' . $this->value);
		return $this;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $name
	 */
	public function alreadyExists($name) {
		$this->name = $name;
		$this->error(self::ERROR_ALREADY_EXISTS);
	}

	/**
	*
	* Enter description here ...
	* @param unknown_type $name
	*/
	public function unexpectedFormat($name) {
		$this->name = $name;
		$this->error(self::ERROR_UNEXPECTED_FORMAT);
	}

	/**
	*
	* Enter description here ...
	* @param unknown_type $message
	* @param unknown_type $code
	*/
	public function error($code) {
		self::$errors[$this->entityName][$this->name][] = $code;
	}
	
	/**
	 *
	 * Enter description here ...
	 */
	public function notSet($name) {
		$this->name = $name;
		$this->error(self::ERROR_NOT_SET);
	}
	
	/**
	 *
	 * Enter description here ...
	 * @throws \Exception
	 */
	public function isValid($field = null) {
		if($field === null) {
			// Check the whole validator
			if(self::$errors) {
				throw new ValidationException(self::$errors);
			}
			return true;
		} else {
			// Check a specific field
			return !isset(self::$errors[$this->entityName][$field]);
		}
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function get() {
		return $this->value;
	}

	/**
	 * 
	 */
	public function getErrors() {
	    return self::$errors;
	}
}