<?php 

namespace Framework;

use Framework\Util\Nomenclature;

use CodeOtter\Rest\Http\Request;

/**
 * 
 * Enter description here ...
 * @author 12dCode
 *
 */
class ResourceManager {

	private $applicationRoot;

	/**
	 * 
	 * Enter description here ...
	 */
	public function __construct($applicationRoot) {
		$this->applicationRoot = $applicationRoot;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $request
	 */
	public function isResource($filepath) {
		return strlen(pathinfo($filepath, PATHINFO_EXTENSION)) > 0;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $file
	 */
	public function get($file) {
		// Prevent unwanted traversal of the project directories
		while(strpos($file, '..') !== false) {
			$file = str_replace('..', ''. $file);
		}

		$pathAsNamespace = substr(Nomenclature::pathToNamespace($file), 1);
		$vendorAndPackage = Nomenclature::getVendorAndPackage($pathAsNamespace);
		$fileRequest = substr($file, strlen($vendorAndPackage) + 2);

		if($result = $this->getResource($this->applicationRoot . '/Public/' . $fileRequest)) {
			return $result;
		}

		$result = $this->getResource($this->applicationRoot . '/' . Core::PATH_PACKAGES . '/'. Nomenclature::toPath($vendorAndPackage) . '/Public/' . $fileRequest);
		if($result) {
			return $result;
		}
		return null;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $filepath
	 */
	public function getResource($filepath) {
		if(is_file($filepath)) {
			return file_get_contents($filepath);
		} else {
			return false;
		}
	}
}