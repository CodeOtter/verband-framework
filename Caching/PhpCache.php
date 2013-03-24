<?php

namespace Verband\Framework\Caching;

use Verband\Framework\Compiler;

class PhpCache extends Cache {
	
	private
		$filename;
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $filename
	 * @param unknown_type $targetDirection
	 */
	public function __construct($filename, $targetDirection) {
		$this->filename = $filename;
		$this->compiler = new Compiler($this->filename, $targetDirection);
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function build() {
		if(!file_exists($this->filename)) {
			$this->compiler->compileCache();
		}
	}
	
	public function add($key, $value) {
		
	}
	
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $key
	 */
	public function get($key) {
	}
}