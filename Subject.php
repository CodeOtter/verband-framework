<?php

namespace Framework;

use Framework\Util\MimeType;

use Framework\Util\Nomenclature;

use Framework\Http\Response;

/**
 * Converts a class into a Subject, which allows access to common developer methods.
 */
class Subject extends Node{
	
	protected static 
		$annotationReader = null;

	private
		$context,
		$annotationCache = array();
		
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $context
	 */
	public function __construct($context = null) {
		$this->context = $context;
	}
	
	/**
	* Returns the Entity Manager
	* @return	EntityManager
	*/
	protected function getEntityManager($name = null) {
		return $this->context->getState('entityManager');
	}

	/**
	 * 
	 * Enter description here ...
	 */
	protected function getFramework() {
		return $this->context->getState('framework');
	}

	/**
	 * 
	 * Enter description here ...
	 */
	protected function getRootPath() {
		return $this->getFramework()->getPath(Core::PATH_ROOT);
	}

	/**
	 * 
	 * Enter description here ...
	 */
	protected function getApplicationPath() {
		return $this->getFramework()->getPath(Core::PATH_APPLICATION);
	}

	/**
	 * 
	 * Enter description here ...
	 */
	protected function getPackagesPath() {
		return $this->getFramework()->getPath(Core::PATH_PACKAGES);
	}

	protected function getSetting($path, $default = null, $deep = true) {
		return $this->getFramework()->getSetting($path, $default, $deep);
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	protected function getLogPath() {
		return $this->getFramework()->getPath(Core::PATH_LOG);
	}

	/**
	 * 
	 * Enter description here ...
	 */
	protected function getCachePath() {
		return $this->getFramework()->getPath(Core::PATH_CACHE);
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $packageName
	 */
	protected function getPackage($packageName = null) {
		return $this->getFramework()->getPackage($packageName);
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $packageName
	 */
	protected function getController($controllerName) {
		return $this->getPackage(Nomenclature::getVendorAndPackage($controllerName))->getController($controllerName);	
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	protected function getContext() {
		return $this->context;
	}

	/**
	* Sets the route associated with this controller's invocation.
	* @param	array
	* @return void
	*/
	protected function setContext($context) {
		$this->context= $context;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $name
	 */
	protected function getState($name) {
		return $this->context->getState($name);
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	protected function setState($name, $value) {
		return $this->context->setState($name, $value);
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $object
	 * @param unknown_type $method
	 */
	protected function getAnnotation($annotation, $object = null, $method = null) {
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
	protected function getPublicAsset($fileName, $parameters = array()) {
		
		$filePath = $this->getPackagesPath() . '/' . Nomenclature::toPath(Nomenclature::getVendorAndPackage($this)) . '/Public/' . $fileName;

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
	protected function createEntity() {
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
	protected function getRepository() {
		return $this->getEntityManager()->getRepository($this->getAnnotation('entity'));
	}

	/**
	 * Returns the Context the controller was invoked with.
	 * @return \Framework\Context
	 */
	protected function getRequest() {
		return $this->context->getState('request');
	}

	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $exception
	 * @param unknown_type $response
	 */
	protected function getErrorResponse($exception) {
		$code = $exception->getCode();
	
		if(!$code || $code < 200) {
			$code = 500;
		}
	
		return new Response($exception->getMessage(), $code);
	}
}