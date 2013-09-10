<?php 

namespace Verband\Framework\Structure;

/**
 * An Package contains a Context tree that is integrated into the Framework's process flow.  
 */
use Verband\Framework\Util\Nomenclature;

abstract class Package {

	/**
	 * The directory of the package
	 */
	protected $directory;

	/**
	* The directory of the package
	*/
	protected $controllers;
	
	/**
	 * Constructor
	 * @param	string	Directory of the package
	 */
	public function __construct($directory) {
		$this->directory = $directory;
		$this->controllers = array();
	}
	
	/**
	 * The initialization process that is executed when a Package is created.
	 * @return void 
	 */
	abstract public function init($contexts);

	/**
	 * Returns the name of the package. 
	 * @return	string
	 */
	public function getName() {
		return Nomenclature::getVendorAndPackage($this);
	}
	
	/**
	 * Returns the directory of the package.
	 * @return	string
	 */
	public function getDirectory() {
		return $this->directory;
	}
	
	/**
	 * Registers custom namespaces
	 */
	/*public function registerNamespaces($autoloader, $packagesPath) {
		foreach($this->getNamespaces($packagesPath) as $namespace => $path) {
			$autoloader->setPath($namespace, $path);
		}

		$autoloader->setPath(Nomenclature::getVendorAndPackage($this), $packagesPath);
	}*/

	/**
	 * 
	 * Enter description here ...
	 */
	public function registerCommands() {
		
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function registerConfiguration() {
		
	}

	/**
	 * Set custom namespaces
	 * @see Framework.Package::addNamespaces()
	 * @return array
	 */
	/*public function getNamespaces($contexts) {
		return array();
	}*/

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $name
	 */
	public function getController($controllerName) {
		if($this->controllerExists($controllerName)) {
			return $this->controllers[$controllerName];
		}
		return false;
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $name
	 */
	public function getControllers() {
        return $this->controllers;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $controllerName
	 */
	public function controllerExists($controllerName) {
		return isset($this->controllers[$controllerName]);
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $controller
	 */
	public function addController($controller) {
		$this->controllers[get_class($controller)] = $controller;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $controllers
	 */
	public function setControllers($controllers) {
		$this->controllers = $controllers;
	}
	
	/**
	 * 
	 */
	public function expand($cacheData) {
	    return true;
	}
	
	/**
	 * 
	 */
	public function compact() {
	   return array(
	        'directory' => $this->directory
       ); 
	}
}