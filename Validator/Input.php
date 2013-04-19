<?php

namespace Verband\Validator;

/**
 * 
 * @author 12dCode
 *
 */
class Input implements \ArrayAccess {
	
	private
		$values;

	/**
	 * 
	 * @param unknown_type $name
	 */
	public function __construct($values) {
		$this->values = $values;
		foreach($this->values as $key => $value) {
			$this->__set($key, $value);
		}
	}

	/**
	 * 
	 * @param unknown_type $key
	 */
	public function __get($key) {
		if(is_object($this->values)) {
			if(isset($this->values->$key)) {
				return $this->values->$key;
			}
		} else if(is_array($this->values)) {
			if(isset($this->values[$key])) {
				return $this->values[$key];
			}
		} else {
			return $this->values;
		}
		return null;
	}
	
	/**
	 * 
	 * @param unknown_type $key
	 * @param unknown_type $value
	 */
	public function __set($key, $value) {
		if(is_array($this->values)) {
			if(is_scalar($value)) {
				$this->values[$key] = $value;
			} else {
				$this->values[$key] = new Input($value);
			}
		} else if(is_object($this->values)) {
			if(is_scalar($value)) {
				$this->values->$key = $value;
			} else {
				$this->values->$key = new Input($value);
			}
		}
	}

}