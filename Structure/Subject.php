<?php

namespace Verband\Framework\Structure;

use Verband\Framework\Util\MimeType;
use Verband\Framework\Util\Nomenclature;
use Symfony\Component\HttpFoundation\Response;
use Verband\Framework\Core;
use Verband\Framework\Util\AnnotationReader;

/**
 * Converts a class into a Subject, which allows access to common developer methods.
 */
class Subject extends Node {
	
	protected static 
		$annotationReader = null,
		$instances = null;

	private
		$context,
		$annotationCache = array();
		
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $context
	 */
	public function __construct($context = null) {
		if(self::$instances === null) {
			self::$instances = array();
		}

		self::$instances[get_class($this)] =  $this;
		$this->context = $context;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $className
	 */
	public static function getInstance($className, $context = null) {
		if(!isset(self::$instances[$className])) {
			return new $className($context);
		}

		return self::$instances[$className];
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $class
	 */
	public static function addInstance($class) {
		self::$instances[get_class($class)] = $class;
		return true;
	}
	
	/**
	* Returns the Entity Manager
	* @return	EntityManager
	*/
	public function getEntityManager($name = null) {
		return $this->context->getState('entityManager');
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function getFramework() {
		return $this->context->getState('framework');
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function getRootPath() {
		return $this->getFramework()->getPath(Core::PATH_ROOT);
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function getApplicationPath() {
		return $this->getFramework()->getPath(Core::PATH_APPLICATION);
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function getPackagesPath() {
		return $this->getFramework()->getPath(Core::PATH_PACKAGES);
	}

	public function getSetting($path, $default = null, $deep = true) {
		return $this->getFramework()->getSetting($path, $default, $deep);
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function getLogPath() {
		return $this->getFramework()->getPath(Core::PATH_LOG);
	}

	/**
	 * 
	 * Enter description here ...
	 */
	public function getCachePath() {
		return $this->getFramework()->getPath(Core::PATH_CACHE);
	}

	/**
	 *
	 */
	public function getSession() {
		return $this->getContext()->getState('session');
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $packageName
	 */
	public function getPackage($packageName = null) {
	    if($packageName === null) {
	        $packageName = strtolower(Nomenclature::getVendorAndPackage($this));
	    }

		return $this->getFramework()->getPackage($packageName);
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $packageName
	 */
	public function getController($controllerName) {
		if(!isset(self::$instances[$controllerName])) {
		    $package = $this->getPackage(Nomenclature::getVendorAndPackage($controllerName));
		    if(!$package) {
		        $controller = new $controllerName();
		        $controller->setContext($this->getContext());
		        return $controller;
		        //return false;
		    }

		    if(!$package || !$package->controllerExists($controllerName)) {
		        $controller = new $controllerName();
		        $controller->setContext($this->getContext());
		        $package->addController($controller);
		    } else {
		        $controller = $package->getController($controllerName);
		    }
		} else {
		    $controller = self::$instances[$controllerName];
		}
		return $controller;
	}

	/**
	* Returns the route associated with this controller's invocation.
	* @return array
	*/
	public function getService($serviceName = null, $context = null) {
		if($serviceName === null) {
			$serviceName = Nomenclature::toServiceName($this->getAnnotation('entity'));
		}

		if($context === null) {
		    $context = $this->getContext();
		}
		
		return new $serviceName($context);
	}

	/**
	 * Sets the route associated with this controller's invocation.
	 * @param	array
	 * @return void
	 */
	public function setContext($context) {
		$this->context= $context;
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $name
	 */
	public function getState($name) {
		return $this->context->getState($name);
	}

	/**
	 * 
	 */
	public function getEnvironment() {
	    return $this->context->getState('framework')->getEnvironment();
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function setState($name, $value) {
		return $this->context->setState($name, $value);
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $object
	 * @param unknown_type $method
	 */
	public function getAnnotation($annotation, $object = null, $method = null) {
		if(self::$annotationReader === null) {
			self::$annotationReader = new AnnotationReader();
		}

		if($object === null) {
			$object = $this;
		}

		$key = $annotation . '.' . get_class($object) . '.' . $method;

		if(!isset($this->annotationCache[$key])) {
			if($method === null) {
				$annotations = self::$annotationReader->read(new \ReflectionClass($object));
			} else {
				$annotations = self::$annotationReader->read(new \ReflectionMethod($object, $method));
			}

			if(isset( $annotations[$annotation])) {
				$this->annotationCache[$key] = $annotations[$annotation];
			} else {
				$this->annotationCache[$key] = null;
			}
		}

		return $this->annotationCache[$key];
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $fileName
	 * @throws \Exception
	 */
	public function getPublicAsset($fileName, $parameters = array()) {
	    $filePath = $this->getPackage()->getDirectory() . '/Public/' . $fileName;

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
	
	/**
	 *
	 * Enter description here ...
	 */
	public function createEntity() {
		$entity = $this->getAnnotation('entity');
		if($entity) {
			return new $entity;
		}
		return false;
	}
	
	/**
	 *
	 * Enter description here ...
	 */
	public function getRepository($repositoryName = null) {
	    if($repositoryName === null) {
	        $repositoryName = $this->getAnnotation('entity');
	    }
	    return $this->getEntityManager()->getRepository($repositoryName);
	}

	/**
	 * Returns the Context the controller was invoked with.
	 * @return \Framework\Context
	 */
	public function getRequest() {
		return $this->context->getState('request');
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $exception
	 * @param unknown_type $response
	 */
	public function getErrorResponse(\Exception $exception, $errorCode = 500) {
		return new Response($exception->getMessage(), $errorCode);
	}
}