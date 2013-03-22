<?php 

namespace Framework;

/**
 * Establishes the same autoloading methodology as Symfony (PSR-0)
 */
class Autoloader {

	private $paths = array();
	
	/**
	 * Constructor
	 * @param	string	The root path of the application
	 * @return	void
	 */
	public function __construct($rootPath) {
		set_include_path($rootPath . '/' . get_include_path());
		$self = $this;
		spl_autoload_register(function($className) use ($self) {
			$filename = str_replace('\\', '/', ltrim($self->findFileByClassname($className), '\\')) . '.php';
			if(!@include($filename)) {
				throw new \Exception('"' . $filename . '" does not exist and cannot be loaded');
			}
		}, true);
	}

	/**
	 * Set the path for a root namespace.
	 * @param	string	Root Namespace
	 * @param	string	Path
	 * @return	void
	 */
	public function setPath($rootNamespace, $path) {
		$this->paths[$rootNamespace] = $path;
	}

	/**
	 * Gets the directory for a namespace based on root.
	 * @param	string	Namespace
	 * @return	string
	 */
	public function findFileByClassname($className) {
		foreach($this->paths as $index => $path) {
			if(substr($className, 0, strlen($index)) == $index) {
				return $this->paths[$index].'/'.$className;
			}
		}
		return $className;
	}
}

