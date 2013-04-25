<?php 

namespace Verband\Framework\Routing;

use Verband\Framework\Core;
use Verband\Framework\Util\Nomenclature;
use CodeOtter\Rest\Http\Request;

/**
 * 
 * Enter description here ...
 * @author 12dCode
 *
 */
class Router {

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
	public function isResource($request) {
		$uri = $request->getRequestUri();
		return strlen(pathinfo($uri, PATHINFO_EXTENSION)) > 0 || ($uri == '/' && in_array('text/html', $request->getAcceptableContentTypes()));
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $file
	 */
	public function get($file) {
		// Prevent unwanted traversal of the project directories
		while(strpos($file, '..') !== false) {
			$file = str_replace('..', '' . $file);
		}

		if($file == '/') {
			$file = '/index.html';
		}

		if($result = $this->getResource($this->applicationRoot . '/application/Public' . $file)) {
			// @TODO: Set path caching here
			return $result;
		}

		$pathAsNamespace = substr(Nomenclature::pathToNamespace($file), 1);
		$vendorAndPackage = strtolower(Nomenclature::getVendorAndPackage($pathAsNamespace));
		$fileRequest = substr($file, strlen($vendorAndPackage) + 2);
		
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