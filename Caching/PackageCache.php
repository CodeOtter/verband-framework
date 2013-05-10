<?php

namespace Verband\Framework\Caching;

class PackageCache  extends Cache {
    
    private
        /**
         *
        */
        $cache = array(),
        
        /**
         *
        */
        $cacheFile = '',
        
        /**
         *
        */
        $rebuild = false;
    
    private
        $accessorClass;
    
    public function __construct($cacheFile) {
        if(!$this->cache) {
            self::load();
        }
        
        $this->cacheFile = $cacheFile;
        
        $this->accessorClass = get_class($this);
    
        // Create a cache for the accessor
        if(!isset($this->cache)) {
            $this->triggerRebuild();
        }
    }
       
    /**
     *
     * Enter description here ...
     */
    public function isEmpty() {
        return count($this->cache) == 0;
    }
    
    /**
     *
     * Enter description here ...
     */
    public function isNotEmpty() {
        return count($this->cache) > 0;
    }
    
    /**
     *
     * Enter description here ...
     */
    public function triggerRebuild() {
        $this->rebuild = true;
    }
    
    /**
     *
     * Enter description here ...
     * @param unknown_type $list
     */
    public function setAll($list) {
        $this->cache = $list;
        $this->triggerRebuild();
        return true;
    }
    
    /**
     *
     * Enter description here ...
     * @param unknown_type $key
     * @param unknown_type $value
     */
    public function set($key, $value) {
        if(isset($this->cache[$key])) {
            if($this->cache[$key] !== $value) {
                $this->triggerRebuild();
            } else {
                return true;
            }
        } else {
            $this->triggerRebuild();
        }
        $this->cache[$key] = $value;
        return true;
    }
    
    /**
     *
     * Enter description here ...
     * @param unknown_type $key
     * @param unknown_type $value
     */
    public function add($key, $value) {
        if(!isset($this->cache[$key])) {
            $this->cache[$key] = array();
        }
        $this->cache[$key][key($value)] = current($value);
        $this->triggerRebuild();
        return true;
    }
    
    /**
     *
     * Enter description here ...
     * @param unknown_type $key
     */
    public function get($key) {
        if(isset($this->cache[$key])) {
            return $this->cache[$key];
        } else {
            return null;
        }
    }
    
    /**
     *
     * Enter description here ...
     * @param unknown_type $callback
     */
    public function sort($callback) {
        return uasort($this->cache, $callback);
    }
    
    /**
     *
     * Enter description here ...
     */
    public function getAll() {
        if(isset($this->cache)) {
            return $this->cache;
        } else {
            return null;
        }
    }
    
    /**
     *
     * Enter description here ...
     * @param unknown_type $source
     */
    public function rebuild() {
        $cacheDirectory = dirname($this->cacheFile);
        if(!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0755, true);
        }
    
        if($this->rebuild) {
            file_put_contents($this->cacheFile, serialize($this->cache));
        }
    }
    
    /**
     *
     * Enter description here ...
     * @param unknown_type $source
     */
    public function load() {
        if(file_exists($this->cacheFile)) {
            $this->cache = unserialize(file_get_contents($this->cacheFile));
        }
    }
}