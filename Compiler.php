<?php

namespace Framework;

class Compiler {

	private
		$filename,
		$targetDirectory,
		$exclude;

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $contexts
	 */
	public function __construct($filename, $targetDirectory, $exclude = array()) {
		$this->filename = $filename;
		$this->targetDirectory = $targetDirectory;

		$regex = '';
		if($exclude) {
			$regex = implode('|', $exclude);
			$regex = str_replace('.', '\.', $regex);
			$regex = str_replace('-', '\-', $regex);
			$regex = str_replace('/', '\/', $regex);
			$regex =  str_replace('*', '.*', $regex);
		}
		$this->exclude = '/' . $regex . '/';
	}

	/**
	 * 
	 * Enter description here ...
	 * @throws \Exception
	 */
	public function compileCache() {
		if(file_exists($this->filename)) {
			unlink($this->filename);
		}

		$this->exclude = '/\/test\/|\/Test\/|\/test\/|\/Tests\//';
		
		$classes = array();

		$output = '<' . '?' . 'php '."\n";

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->targetDirectory), \RecursiveIteratorIterator::SELF_FIRST);

		foreach($iterator as $file) {
			if(!$iterator->isDot() && $file->isFile() && $this->isPhpFile($file) && $this->isValidPath($file)) {
				$filename = (string)$file;
				if(strpos($filename, '/Framework/') !== false || strpos($filename, '/Packages/') !== false) {
					// Dealing with legit files
					$output .= $this->compilePhp($filename);
				}
			}
		}

		file_put_contents($this->filename, $output);
		return true;
	}

	public function compilePhp($filename) {
		$matches = array();
		$contents = php_strip_whitespace($filename);

		$namespace = '';
		$namespaceFound = false;

		// Find the namespace.. through tokenization... yikes :X
		foreach(token_get_all($contents) as $token) {
			if(!is_string($token)) {
				// Dealing with a token array
				if($namespaceFound) {
					if($token[0] == T_STRING || $token[0] == T_NS_SEPARATOR) {
						$namespace .= $token[1];
					}
				} else if($token[0] == T_NAMESPACE) {
					$namespaceFound = true;
					continue;
				}
			} else {
				// We've gathered the namespace
				if($namespaceFound && $token == ';') {
					break;
				}
			}
		} 

		if($namespace) {
			// Dealing with a PSR-0 compliant file
			$code = trim(substr($contents, strpos($contents, 'namespace ' . $namespace . ';') + strlen($namespace) + 11));
			$lastSectionIndex = strlen($code) - 2;
			$lastSection = substr($code, $lastSectionIndex);
			if($lastSection == '?>') {
				$code = substr($code, 0, $lastSectionIndex);
			}

			return 'namespace ' . $namespace . ' {' . $code . "}\n";
		}

		return '';
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function compilePhar($privateKeyPath = null, $publicKeyPath = null) {
		if(file_exists($this->filename)) {
			unlink($this->filename);
		}

		$phar = new \Phar($this->filename);
		$phar->setStub($this->targetDirectory . '/Application/Stub.php');
		exit;

		if($privateKeyPath !== null && !file_exists($privateKeyPath)) {
				throw new \Exception($privateKeyPath . ' does not exist.');
		}
		
		if($publicKeyPath !== null && !file_exists($publicKeyPath)) {
			throw new \Exception($publicKeyPath . ' does not exist.');
		}

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->targetDirectory), \RecursiveIteratorIterator::SELF_FIRST);

		foreach($iterator as $file) {
			if(!$iterator->isDot() && $this->isValidPath($file)) {
				// We are dealing with a valid, compilable resource
				if($file->isFile()) {
					// Dealing with a file
					if($this->isPhpFile($file)) {
						// Dealing with a PHP file
						$phar->addFromString($this->getRelativePath($file), php_strip_whitespace((string)$file));
					} else {
						//  Dealing with a non-PHP file
						$phar->addFile($file, $this->getRelativePath($file));
					}
				} else if($file->isDir()) {
					// Dealing with a directory (?)
					$phar->addEmptyDir($this->getRelativePath($file));
				}
			}
		}

		if($privateKeyPath !== null) {
			$phar->setSignatureAlgorithm(Phar::OPENSSL, $privateKeyPath);
			copy($publicKeyPath, $this->filename . '.pubkey');
		}

		return true;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $file
	 */
	private function isValidPath($file) {
		return !preg_match($this->exclude, (string)$file);
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $file
	 */
	private function isPhpFile($file) {
		return pathinfo((string)$file, PATHINFO_EXTENSION) == 'php';
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $needle
	 * @param unknown_type $haystack
	 */
	private function getRelativePath($file) {
		if (strpos($file, $this->targetDirectory) !== 0) {
			return $file;
		}

		return substr($file, strlen($this->targetDirectory));
	}
}