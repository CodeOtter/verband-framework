<?php

namespace Verband\Framework\Test;

class MockMap {

    
    
    private $map = array();

    
    public function __construct() {
        
    }
    
    /**
     * 
     * @param unknown_type $arguments
     * @param unknown_type $result
     */
    public function add($originals, $constraints) {
        // @TODO: Do raw analysis of values here
        $this->map[] = [
            'constraints' => $originals,
            'originals'   => $constraints,
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
            foreach($values as $index => $value) {
                if($value !== $element['originals'][$index]) {
                    break;
                }
                $found = true;
                break;
            }

            if($found) {
                break;
            }
        }

        if(!$found) {
            throw new \Exception('Cannot find argument combination in the MockMap.');
        }

        return $element['result'];
    }

    /**
     * 
     * @param unknown_type $result
     */
    public function addResult($result) {
        $this->map[count($this->map) - 1]['result'] = $result;
    }

    /**
     * 
     */
    public function popConstraints() {
        return array_pop($this->map)['constraints'];
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