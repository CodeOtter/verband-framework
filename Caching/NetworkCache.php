<?php

namespace Verband\Framework\Caching;

class NetworkCache extends Cache {

	private
		$connection;
	
	public function __construct() {
		
	}
	
	public function set($key, $value) {
		return $this->connection->add($key, $value);
	}
	
	public function get($key) {
		return $this->connection->get($key);
	}
	
	public function save($source) {
		return;
	}
	
	public function load($source) {
		return;
	} 
}