<?php 

namespace Verband\Framework\Routing;

use Verband\Framework\Util\MimeType;

use Verband\Framework\Structure\Subject;
use Verband\Framework\Core;
use Verband\Framework\Util\Nomenclature;
use CodeOtter\Rest\Http\Request;

/**
 * 
 * Enter description here ...
 * @author 12dCode
 *
 */
class Router extends Subject {

	private $applicationRoot;

	/**
	 * 
	 * Enter description here ...
	 */
	public function __construct($applicationRoot, $context) {
		$this->applicationRoot = $applicationRoot;
		parent::__construct($context);
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $request
	 */
	public function isResourceRequest($request) {
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

		$file = substr($file, strlen($this->getSetting('Application[webRoot]')));

		if($file == '/') {
			$file = '/index.html';
		}

		if($result = $this->getResource($this->applicationRoot . '/Application/Public' . $file)) {
			// @TODO: Set path caching here
			return $result;
		}

		$pathAsNamespace = substr(Nomenclature::pathToNamespace($file), 1);
		$vendorAndPackage = strtolower(Nomenclature::getVendorAndPackage($pathAsNamespace));
		$fileRequest = substr($file, strlen($vendorAndPackage) + 2);
        $packagePath = $this->applicationRoot . '/' . Core::PATH_PACKAGES . '/'. Nomenclature::toPath($vendorAndPackage);
        $properPackageName = Nomenclature::toPath($this->getContext()->getState('framework')->findStartup($packagePath));
		$result = $this->getResource($packagePath . '/' . $properPackageName . '/Public/' . $fileRequest);

		if($result) {
			return $result;
		}

		return false;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $filepath
	 */
	public function getResource($filePath) {
		if(is_file($filePath)) {
		    if(MimeType::isParsable($filePath, $this->getSetting('Application[webServer][parsables]'))) {
		        // Parse a PHP file
		        ob_start();
		        include $filePath;
		        $contents = ob_get_contents();
		        ob_end_clean();
		    } else {
		        // Open a file
		        // @TODO: Put file caching strategy here
		        $contents = file_get_contents($filePath);
		    }
		    return $contents;
		} else {
			return false;
		}
	}
}