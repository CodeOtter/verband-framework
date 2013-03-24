<?php 

namespace Verband\Framework;

/**
 * Extracts the annotation from a class, method, or function.
 */
class AnnotationReader {

	/**
	 * @todo	Fill this out
	 * @throws \Exception
	 */
	public function read($target) {
		// Parse object
		if(!($target instanceof \Reflector)) {
			if(is_array($target)) {
				$target = new \ReflectionMethod($target);
			} elseif(is_object($target)) {
				$target = new \ReflectionClass($target);
			} elseif(is_string($target)) {
				if(strstr($target. '\\')) {
					$target = new \ReflectionClass($target);
				} else {
					$target = new \ReflectFunction($target);
				}
			}
		}

		if($target instanceof \Reflector) {
			return $this->parse($target->getDocComment());
		} else {
			throw new \Exception('Unknown target type for annotation reading.');
		}
	}

	/**
	 * @todo	Fill this out
	 */
	private function parse($rawAnnotation) {
		$result = array();
		$matches = array();
		preg_match_all('/\s*\*\s*@([^\s]+)\s*(.*)/', $rawAnnotation, $matches);
		foreach($matches[1] as $index => $field) {
			$result[strtolower($field)] = trim($matches[2][$index]);
		}
		return $result;
	}
}