<?php

namespace Verband\Framework\Test;

use Verband\Framework\Structure\Context;
use Verband\Framework\Structure\Subject;

/**
 * Verband Unit testing with plain english mocking.
 * @example
 * $someObject = $this->getMock('Verband\Framework\Test\SomeObject');
 * $this->should($someObject, 'customMethod uses (5) and returns "foo"');
 * $this->should($someObject, 'customMethod uses (5) once and returns "foo"');
 * $this->should($someObject, 'customMethod uses (5) at least once and returns "foo"');
 * $this->should($someObject, 'customMethod uses (5) never and returns "foo"');
 * $this->should($someObject, 'customMethod uses (5) 3 times and returns "foo"');
 * $this->should($someObject, 'customMethod returns the 1st argument');
 * $this->should($someObject, 'customMethod returns the result of md5');
 * $this->should($someObject, 'customMethod uses [1, 4, 5, 7]');
 * $this->should($someObject, 'customMethod returns [1, 4, 5, 7]');
 * $this->should($someObject, 'customMethod uses [1, 4, 5, 7] and returns [1, 2, 3, 4]');
 * $this->should($someObject, 'customMethod throws \Exception');
 * $this->should($someObject, 'customMethod uses (>0, %something%, *)');
 * $this->should($someObject, 'customMethod returns {object}', array('object' => new \StdClass));
 * 
 */
trait VerbandTestTrait {

    /**
     * 
     * @param Context $contexts
     */
    public static function setSubject(Subject $subject) {
        self::$subject = $subject;
    } 

    /**
     * @return
     */
    public function getSubject() {
        return self::$subject;
    }
    
    /**
     *
     * @param unknown_type $mock
     * @param unknown_type $rules
     * @param unknown_type $parameters
     */
    public function should($mock, $rules, $parameters = array()) {
        $matches = array();
        $pattern = '/([a-zA-Z_]+)( uses)?( [\(|\[][^\)\[]+[\)|\]])?( at least once| once| never| [0-9]+ times)?( and returns| and throws| returns| throws)?( .+)?/';
        preg_match($pattern, $rules, $matches);
        $matches = array_map('trim', $matches);
    
        if(isset($matches[3]) && $matches[3] != '' && $matches[3][0] == '[') {
            // Dealing with consecutive checks
            $ats = array_map('trim', str_getcsv(substr($matches[3], 1, strlen($matches[3]) - 2)));
    
            if(isset($matches[6]) && $matches[6][0] == '[') {
                $values = array_map('trim', str_getcsv(substr($matches[6], 1, strlen($matches[6]) - 2)));
            } else {
                $values = array();
            }
    
            foreach($ats as $at => $argument) {
                $expects = $mock->expects($this->at($at));
                $method = $expects->method($matches[1]);
                $method = $this->finish($method, 'uses', '(' . $argument . ')', $parameters);
    
                if(isset($values[$at])) {
                    $this->finish($method, 'returns', $values[$at], $parameters);
                }
            }
            return;
        } else if(isset($matches[4]) && $matches[4] != '') {
            switch($matches[4]) {
                case 'at least once': $expects = $mock->expects($this->atLeastOnce()); break;
                case 'once':          $expects = $mock->expects($this->once()); break;
                case 'never':         $expects = $mock->expects($this->never()); break;
                default:
                    $quantity = array();
                    preg_match('/([0-9]+) times/', $matches[4], $quantity);
                    if(count($matches) > 1) {
                        $expects = $mock->expects($this->exactly($quantity[1]));
                    } else {
                        $expects = $mock->expects($this->any());
                    }
            }
        } else {
            $expects = $mock->expects($this->any());
        }
    
        $method = $expects->method($matches[1]);
    
        if(isset($matches[2]) && $matches[2] != '') {
            $method = $this->finish($method, $matches[2], $matches[3], $parameters);
        }
    
        if(isset($matches[5]) && $matches[5] != '') {
            $this->finish($method, $matches[5], $matches[6], $parameters);
        }
    }
    
    /**
     *
     * @param unknown_type $value
     * @return NULL|number|string|unknown
     */
    public function extract($value) {
        if($value == 'null') {
            return null;
        } elseif(is_numeric($value)) {
            if(strstr($value, '.') !== false) {
                return (float)$value;
            } else {
                return (int)$value;
            }
        } elseif($value[0] == '"') {
            return substr($value, 1, strlen($value) - 2);
        }
        return $value;
    }
    
    /**
     *
     * @param unknown_type $method
     * @param unknown_type $type
     * @param unknown_type $value
     * @param unknown_type $parameters
     * @return mixed
     */
    private function parse($method, $type, $value, $parameters) {
        if($type == 'throws') {
            return $method->will($this->throwException(new $value));
        } elseif($type=== 'uses') {
            $arguments = array_map('trim', str_getcsv(substr($value, 1, strlen($value) - 2)));
            if($value[0] == '(') {
                // Extract values
                $values = array();
                foreach($arguments as $argument) {
                    if($argument[0] == '<' || $argument[0] == '>') {
                        $matches = array();
                        preg_match('/([^0-9]+)([0-9]+)/', $argument, $matches);
                        switch($matches[1]) {
                            case '<':   $values[] = $this->lessThan($matches[2]); break;
                            case '<=':  $values[] = $this->lessThanOrEqual($matches[2]); break;
                            case '>':   $values[] = $this->greaterThan($matches[2]); break;
                            case '>=':  $values[] = $this->greaterThanOrEqual($matches[2]); break;
                        }
                    } elseif($argument[0] == '%') {
                        $values[] = $this->stringContains(substr($argument, 1, strlen($argument) - 2));
                    } elseif($argument[0] == '{') {
                        //var_dump($argument);
                        $values[] = $this->equalTo($parameters[substr($argument, 1, strlen($argument) - 2)]);
                    } elseif($argument[0] == '*') {
                        $values[] = $this->anything();
                    } else {
                        $values[] = $this->equalTo($this->extract($argument));
                    }
                }
                return call_user_func_array(array($method, 'with'), $values);
            }
        } elseif($type == 'returns') {
            $matches = array();
            if($value[0] == '[') {
                return $method->will(
                        call_user_func_array(
                                array($this, 'onConsecutiveCalls'),
                                array_map(array($this, 'extract'), array_map('trim', str_getcsv(substr($value, 1, strlen($value) - 2))))
                        ));
            } elseif(preg_match('/the ([0-9]+)[a-z]{2} argument/', $value, $matches)) {
                return $method->will($this->returnArgument($matches[1] - 1));
            } elseif(preg_match('/the result of ([a-zA-Z0-9_]+)/', $value, $matches)) {
                return $method->will($this->returnCallback($matches[1]));
            } elseif($value[0] == '{') {
                return $method->will($this->returnValue($parameters[substr($argument, 1, strlen($argument) - 2)]));
            } else {
                return $method->will($this->returnValue($this->extract($value)));
            }
        }
    }
    
    /**
     *
     * @param unknown_type $method
     * @param unknown_type $type
     * @param unknown_type $values
     * @param unknown_type $parameters
     */
    private function finish($method, $type,  $values, $parameters) {
        $type = str_replace('and ', '', $type);
        return $this->parse($method, $type, $values, $parameters);
    }
}