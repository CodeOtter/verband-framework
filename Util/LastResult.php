<?php 

namespace Framework\Util;

/**
 * @todo
 */
class LastResult {

	public static function get($lastResult, $key, $default) {
		if(is_array($lastResult)) {
			if(!isset($lastResult[$key])) {
				return $default;
			} else {
				return $lastResult[$key];
			}
		} elseif(is_object($lastResult)) {
			if(!isset($lastResult->{$key})) {
				return $default;
			} else {
				return $lastResult->{$key};
			}
		} elseif($lastResult === null) {
			return $default;
		} else {
			return $lastResult;
		}
	}
}