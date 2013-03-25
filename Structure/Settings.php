<?php

namespace Verband\Framework\Structure;

use Symfony\Component\Yaml\Yaml;

/**
 * 
 * @author 12dCode
 */
class Settings {
	
	protected
		$filename,
		$contents;
	
	/**
	 * Constructor
	 * @param string
	 */
	public function __construct($filename) {
		$this->open($filename);
	}
	
	/**
	 *
	 * @param unknown_type $filename
	 */
	public function open($filename) {
		$this->filename = $filename;
		$directory = dirname($this->filename);
		if(!file_exists($this->filename)) {
			throw new \Exception('Config does not exist.');
		}

		$this->contents = Yaml::parse($this->filename);
		return $this;
	}

	/**
	 *
	 * @param unknown_type $filename
	 */
	public function save() {
		Yaml::dump($this->contents, 100);
		return $this;
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function getContents() {
		return $this->contents;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $this->contents
	 * @param unknown_type $settings
	 */
	public function merge($settings) {
	    foreach($settings as $k => $v) {
	        if(is_array($v)) {
	            if(!isset($this->contents[$k])) {
	                $this->contents[$k] = $v;
	            } else {
	                $this->contents[$k] = $this->merge($this->contents[$k], $v);
	            }
	        } else {
	            $this->contents[$k] = $v;
	        }
	    }
	    return $this;
	}
}