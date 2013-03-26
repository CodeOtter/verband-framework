<?php

namespace Verband\Framework\Caching;

class FileCache extends Cache {

	private static
		/**
		 * 
		 */
		$cache = array(),

		/**
		 * 
		 */
		$cacheFile = '',

		/**
		 * 
		 */
		$rebuild = false;

	private
		$accessorClass;

	public function __construct() {
		if(!self::$cache) {
			self::load();
		}

		// @TODO: Initialize contents?

		$this->accessorClass = get_class($this);

		// Create a cache for the accessor
		if(!isset(self::$cache[$this->accessorClass])) {
			self::$cache[$this->accessorClass] = array();
			$this->triggerRebuild();
		}
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $cacheFile
	 */
	public static function setCacheFile($cacheFile) {
		self::$cacheFile = $cacheFile;
	}

	/**
	 *
	 * Enter description here ...
	 */
	public function isEmpty() {
		return count(self::$cache[$this->accessorClass]) == 0;
	}

	/**
	 *
	 * Enter description here ...
	 */
	public function isNotEmpty() {
		return count(self::$cache[$this->accessorClass]) > 0;
	}

	/**
	 *
	 * Enter description here ...
	 */
	public function triggerRebuild() {
		self::$rebuild = true;
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $key
	 * @param unknown_type $value
	 */
	public function set($key, $value) {
		if(isset(self::$cache[$this->accessorClass][$key])) {
			if(self::$cache[$this->accessorClass][$key] !== $value) {
				$this->triggerRebuild();
			} else {
				return true;
			}
		} else {
			$this->triggerRebuild();
		}
		self::$cache[$this->accessorClass][$key] = $value;
		return true;
	}

	/**
	*
	* Enter description here ...
	* @param unknown_type $key
	* @param unknown_type $value
	*/
	public function add($key, $value) {
		if(!isset(self::$cache[$this->accessorClass][$key])) {
			self::$cache[$this->accessorClass][$key] = array();
		}
		self::$cache[$this->accessorClass][$key][key($value)] = current($value);
		$this->triggerRebuild();
		return true;
	}
	
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $key
	 */
	public function get($key) {
		if(isset(self::$cache[$this->accessorClass][$key])) {
			return self::$cache[$this->accessorClass][$key];
		} else {
			return null;
		}
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $callback
	 */
	public function sort($callback) {
		return uasort(self::$cache[$this->accessorClass], $callback);
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function getAll() {
		if(isset(self::$cache[$this->accessorClass])) {
			return self::$cache[$this->accessorClass];
		} else {
			return null;
		}
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $source
	 */
	public static function rebuild() {
		$cacheDirectory = dirname(self::$cacheFile);
		if(!is_dir($cacheDirectory)) {
			mkdir($cacheDirectory, 0755, true);
		}
		if(self::$rebuild) {
			file_put_contents(self::$cacheFile, serialize(self::$cache));
		}
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $source
	 */
	public function load() {
		if(file_exists(self::$cacheFile)) {
			//self::$cache = unserialize(file_get_contents(self::$cacheFile));
		}
	} 
}