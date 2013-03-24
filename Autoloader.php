<?php 

namespace Verband\Framework;

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
			$filename = ltrim($self->findFileByClassname($className), '\\') . '.php';
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
			$prefix = substr($className, 0, strlen($index));
			if($prefix == $index || strtolower($prefix) == strtolower($index)) {

				$parts = explode('\\', $className);
				
				// Autoload tokens to use
				$filters['Vendor'] = $parts[0];
				$filters['Package'] = $parts[1];

				$i = 3;
				foreach($parts as $part) {
					$filters[(string)$i] = $part;
					$i++;
				}

				$parts[0] = strtolower($parts[0]);
				$parts[1] = strtolower($parts[1]);

				$filters['vendor'] = $parts[0];
				$filters['package'] = $parts[1];
				$filters['composer'] = implode('/', $parts);

				if(strpos($path, '{') === false) {
					// PSR-0 token does not exist, append the directory naming convesion to the end of the path (Default Composer)
					return $path . '/' . $filters['composer'];
				} else {
					// Replace the PSR-0 token with the naming convention (Awkward Composer-meets-legacy framework convensions)
					foreach($filters as $key => $filter) {
						$result = str_replace('{' . $key . '}', $filter, $path);
					}
					return $result;
				}
			}
		}

		$parts = explode('\\', $className);
		$parts[0] = strtolower($parts[0]);
		$parts[1] = strtolower($parts[1]);

		return str_replace('\\', '/', implode('/', $parts));
	}
}

