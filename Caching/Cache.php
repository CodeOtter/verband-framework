<?php

namespace Framework\Caching;

/**
 * Probably should be an interface
 */
abstract class Cache {

	/**
	 * 
	 * Enter description here ...
	 */

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $key
	 * @param unknown_type $value
	 */
	abstract public function set($key, $value);

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $key
	 */
	abstract public function get($key);
}