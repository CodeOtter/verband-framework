<?php 

namespace Verband\Framework\Util;

use Verband\Framework\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\ParameterBag;

class ValidatorMap {

	private
		$map = array(
			'fields' => array(),
			'form' => null
		),
		$validator,
		$service;

	/**
	 *
	 * Enter description here ...
	 */
	public function __construct($entityName, $service) {
		$validator = new Validator($entityName);
		$this->validator = $validator;
		$this->service = $service;
		$this->map['form'] = function ($submittedData) use ($validator, $entityName) {
			if(!is_object($submittedData)) {
				$validator->unexpectedFormat(strtolower($entityName));
			}
		};
	}

	/**
	 * 
	 * @param unknown $fieldName
	 * @param unknown $validate
	 * @param string $default
	 * @param string $update
	 */
	public function addField($fieldName, $validate, $default = null, $update = null) {
		$this->map['fields'][$fieldName] = array(
			'validate' => $validate,
			'default' => $default,
			'update' => $update	
		);
		return $this;
	}

	/**
	 * 
	 * @param unknown $formCheck
	 */
	public function addForm($formCheck) {
		$this->map['form'] = $formCheck;
		return $this;
	}
	
	/**
	 * 
	 * @param unknown $service
	 * @param ParameterBag $submittedData
	 * @return \Symfony\Component\HttpFoundation\ParameterBag
	 */
   public function initialize(ParameterBag $submittedData) {
        $result = new ParameterBag();

        foreach($this->mapform['fields'] as $field => $configuration) {
           $default = $configuration['default'];
           if(is_callback($default)) {
               $value = $default($this->service, $submittedData);
           } else {
               $value = $default;
           }
           $result->set($field,  $submittedData->get($field, $configuration['default']));
        }
        return $result;
    }

    /**
     * 
     * @param unknown $service
     * @param unknown $entity
     * @param ParameterBag $submittedData
     * @return unknown
     */
    public function validate($entity, ParameterBag $submittedData) {
        $this->map['form']($this->service, $submittedData);

        foreach($this->map['fields'] as $field => $configuration) {

        	// Validate
        	if(is_callback($configuration['validate'])) {
        		$value = $configuration['validate']($this->service, $entity, $submittedData);
        	} else {
        		$value = $configuration['validate'];
        	}

        	// Assignment
        	if(is_callable($configuration['update'])) {
       			$entity = $configuration['update']($this->service, $entity, $value);
       		} else {
       			$entity->{'set'.$field}($value);
       		}
        }

        return $entity;
    }
    
    /**
     * 
     * @param string $field
     * @return boolean
     */
    public function isValid($field = null) {
    	return $this->validator->isValid($field);
    }

    /**
     * 
     * @return number
     */
    public function isEmpty() {
    	return count($this->map['fields']);
    }
}