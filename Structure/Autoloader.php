<?php 

namespace Verband\Framework\Structure;

/**
 * Establishes the same autoloading methodology as Symfony (PSR-0)
 */
class Autoloader {

	private $paths = array();
	
	public $filters = array();
	
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
		
		$self = $this;

		// Range
		$this->filters['['] = function($key, $parts)  use ($self) {
			$range = explode('-', substr($key, 0, strlen($key) - 2));
			return implode('/', array_slice($parts, $range[0], $range[1] - $range[0])); 
		};
		
		// Greater than
		$this->filters['>'] = function($key, $parts)  use ($self) {
			return implode('/', array_slice($parts, substr($key, 1) + 1));				
		};
		
		// Less than
		$this->filters['<'] = function($key, $parts)  use ($self) {
			return implode('/', array_slice($parts, 0, substr($key, 1) - 1));
		};
		
		// First
		$this->filters['first'] = function($key, $parts)  use ($self) {
			return $parts[0];
		};

		// Last
		$this->filters['last'] = function($key, $parts)  use ($self) {
			return $parts[count($parts) - 1];
		};

		// Index
		$this->filters['index'] = function($key, $parts)  use ($self) {
				return $parts[$key];
		};
		
		// Composer
		$this->filters['composer'] = function($keys, $parts) use ($self) {
			return strtolower($parts[0] . '/' . $parts[1]) . '/' . $self->filters['>']('>1', $parts);
		};
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

				if(strpos($path, '{') === false) {
					// PSR-0 token does not exist, append the directory naming convesion to the end of the path (Default Composer and Verband)
					return $path . '/' . $this->filters['composer'](null, $parts);
				} else {
					// Replace the PSR-0 token with the naming convention (Awkward Composer-meets-legacy framework conventions)
					$matches = array();
					preg_match_all('/\{([^\}]+)\}/', $path, $matches);

					foreach($matches[1] as $match) {
						$key = explode('.', $match);

						if(isset($this->filters[$key[0]])) {
							// The key is the full match
							$filter = $key[0];
						} else if(isset($this->filters[$key[0][0]])) {
							// The key is the first letter of the match
							$filter = $key[0][0];
						} else {
							// The key is an index
							$filter = 'index';
						}
						
						$replacement = $this->filters[$filter]($key[0], $parts);

						if(isset($key[1]) && $key[1] == 'lc') {
							$replacement = strtolower($replacement);
						}

						$count = 1;

						$path = str_replace('{' . $match. '}', $replacement, $path, $count);
					}
					return $path;
				}
			}
		}
		
		// Default Composer
		$parts = explode('\\', $className);
		$parts[0] = strtolower($parts[0]);
		$parts[1] = strtolower($parts[1]);
		return str_replace('\\', '/', implode('/', $parts));
	}
}

