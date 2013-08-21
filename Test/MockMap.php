<?php

namespace Verband\Framework\Test;

class MockMap {

    
    
    private $map = array();
    private $useContraints = false;

    
    public function __construct() {
        
    }
    
    /**
     * 
     * @param unknown_type $arguments
     * @param unknown_type $result
     */
    public function add($constraints, $originals) {
        foreach($constraints as $constraint) {
            if(get_class($constraint) != 'PHPUnit_Framework_Constraint_IsEqual') {
                $this->useContraints = true;
            }
        }
        // @TODO: Do raw analysis of values here
        $this->map[] = [
            'constraints' => $constraints,
            'originals'   => $originals,
            'result'      => null
        ];
    }
    
    /**
     * 
     * @param unknown_type $values
     * @throws \Exception
     * @return unknown
     */
    private function find($values) {
        $found = false;
        foreach($this->map as $element) {
            if(!$values && !$element['originals']) {
                $found = true;
            } else {
                foreach($values as $index => $value) {
                    if($value !== $element['originals'][$index]) {
                        break;
                    }
                    $found = true;
                    break;
                }   
            }
            if($found) {
                break;
            }
        }

        if(!$found) {
            throw new \Exception('Cannot find argument combination in the MockMap: ' . print_r($values, true));
        }

        return $element['result'];
    }

    /**
     * 
     * @param unknown_type $result
     */
    public function addResult($result) {
        $index = count($this->map) - 1;
        if(!isset($this->map[$index])) {
            $this->map[$index] = array(
                'constraints' => array(),
                'originals'   => array(),
            );
        }
        $this->map[$index]['result'] = $result;
    }

    /**
     * 
     */
    public function popConstraints() {
        return array_pop($this->map)['constraints'];
    }

    /**
     * 
     * @return boolean
     */
    public function useConstraints() {
        return $this->useContraints;
    }
    
    /**
     * 
     * @param unknown_type $method
     */
    public function with($method) {
        return call_user_func_array(array($method, 'with'), $this->popConstraints());
    }

    /**
     * 
     * @return \Verband\Framework\Test\unknown
     */
    public function callback() {
        return $this->find(func_get_args());
    }
}