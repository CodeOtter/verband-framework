<?php

namespace Verband\Framework\Test;

use BotBot\Application\Entity\Contract;

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

    private $mockMaps = array();
     
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
        $pattern = '/([a-zA-Z_]+)( uses)?( [\(|\[][^\)\]]+[\)|\]])?( at least once| once| never| [0-9]+ times)?( and returns| and throws| returns| throws)?( .+)?/';
        preg_match($pattern, $rules, $matches);
        $matches = array_map('trim', $matches);
        $methodIndex = get_class($mock) . '.' . $matches[1];

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
                $method = $this->finish($methodIndex, $method, 'uses', '(' . $argument . ')', $parameters);

                if(isset($values[$at])) {
                    $this->finish($methodIndex, $method, 'returns', $values[$at], $parameters);
                }
            }
            return;
        } else if(isset($matches[4]) && $matches[4] != '') {
            switch($matches[4]) {
                case 'at least once': $expects = $mock->expects($this->atLeastOnce()); break;
                case 'once':          $expects = $mock->expects($this->once());        break;
                case 'never':         $expects = $mock->expects($this->never());       break;
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
            $method = $this->finish($methodIndex, $method, $matches[2], $matches[3], $parameters);
        }

        if(isset($matches[5]) && $matches[5] != '') {
            $this->finish($methodIndex, $method, $matches[5], $matches[6], $parameters);
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
        } elseif($value == 'true') {
            return true;
        } elseif($value == 'false') {
            return false;
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
    private function parse($methodName, $method, $type, $value, $parameters) {
        if($type == 'throws') {
            return $method->will($this->throwException(new $value));
        } elseif($type=== 'uses') {
            $arguments = array_map('trim', str_getcsv(substr($value, 1, strlen($value) - 2)));
            if($value[0] == '(') {
                // Extract values
                $constraints = array();
                $originals = array();
                foreach($arguments as $argument) {
                    if($argument[0] == '<' || $argument[0] == '>') {
                        $matches = array();
                        preg_match('/([^0-9]+)([0-9]+)/', $argument, $matches);
                        $original = $matches[2];
                        switch($matches[1]) {
                            case '<':   $constraints[] = $this->lessThan($original); break;
                            case '<=':  $constraints[] = $this->lessThanOrEqual($original); break;
                            case '>':   $constraints[] = $this->greaterThan($original); break;
                            case '>=':  $constraints[] = $this->greaterThanOrEqual($original); break;
                        }
                    } elseif($argument[0] == '%') {
                        $original = substr($argument, 1, strlen($argument) - 2);
                        $constraints[] = $this->stringContains($original);
                    } elseif($argument[0] == '{') {
                        $original = $parameters[substr($argument, 1, strlen($argument) - 2)];
                        $constraints[] = $this->equalTo($original);
                    } elseif($argument[0] == '*') {
                        $original = null;
                        $constraints[] = $this->anything();
                    } else {
                        $original = $this->extract($argument);
                        $constraints[] = $this->equalTo($original);
                    }

                    $originals[] = $original;
                }
                $this->getMockMap($methodName)->add($constraints, $originals);
                return $method;
            }
        } elseif($type == 'returns') {
            $matches = array();
            $mockMap = $this->getMockMap($methodName);
            if($value[0] == '[') {
                $method = $mockMap->with($method);
                return $method->will(
                        call_user_func_array(
                                array($this, 'onConsecutiveCalls'),
                                array_map(array($this, 'extract'), array_map('trim', str_getcsv(substr($value, 1, strlen($value) - 2))))
                        ));
            } elseif(preg_match('/the ([0-9]+)[a-z]{2} argument/', $value, $matches)) {
                $method = $mockMap->with($method);
                return $method->will($this->returnArgument($matches[1] - 1));
            } elseif(preg_match('/the result of ([a-zA-Z0-9_]+)/', $value, $matches)) {
                $method = $mockMap->with($method);
                return $method->will($this->returnCallback($matches[1]));
            } elseif($mockMap->useConstraints()) {
                $method = $mockMap->with($method);
                return $method->will($this->returnArgument($this->extract($value)));
            } elseif($value[0] == '{') {
                $mockMap->addResult($parameters[substr($value, 1, strlen($value) - 2)]);
                return $method->will($this->returnCallback(array($mockMap, 'callback')));
            } else {
                $mockMap->addResult($this->extract($value));
                return $method->will($this->returnCallback(array($mockMap, 'callback')));
            }
        }
    }

    /**
     *
     * @param unknown_type $methodName
     * @return multitype:
     */
    private function getMockMap($methodName) {
        if(!isset($this->mockMaps[$methodName])) {
            $this->mockMaps[$methodName] = new MockMap();
        }
        return $this->mockMaps[$methodName];
    }

    /**
     *
     * @param unknown_type $method
     * @param unknown_type $type
     * @param unknown_type $values
     * @param unknown_type $parameters
     */
    private function finish($methodName, $method, $type,  $values, $parameters) {
        $type = str_replace('and ', '', $type);
        return $this->parse($methodName, $method, $type, $values, $parameters);
    }

    /**
     * Returns a mock subject to do basic unit tests on
     */
    public function setApplicationState($settings = array(), $states = array(), $repositories = array(), $sessionValues = array(), $loggedInUser = null) {
        // Define framework
        $framework = $this->getMock('Verband\Framework\Core');
        foreach($settings as $name => $value) {
            $this->should($framework, 'getSetting uses ("'.$name.'", null, true) and returns {value}', array('value' => $value));
        }

        // Define Entity Manager
        $entityManager  = $this->getMock('\Doctrine\ORM\EntityManager',  array(), array(), '', false);
        //$this->should($entityManager, 'getClassMetadata uses (*) returns {value}', array('value' => (object)array()));
        $this->should($entityManager, 'remove uses (*) and returns null');
        $this->should($entityManager, 'persist uses (*) and returns null');
        $this->should($entityManager, 'flush returns null');
        
        // Define respostitories
        foreach($repositories as $name => $settings) {
            $repository  = $this->getMock($name,  array(), array(), '', false);
            foreach($settings as $method => $parameters) {
                foreach($parameters as $parameter) {
                    if(isset($parameter['arguments']) && $parameter['arguments']) {
                        $arguments = array();
                        $newParameters = array();
                        foreach($parameter['arguments'] as $index => $argument) {
                            $nexIndex = ':' . $index;
                            $arguments[] = '{' . $nexIndex. '}';
                            $newParameters[$nexIndex] = $argument;
                        }
                        $newParameters['return'] = $parameter['return'];
    
                        $this->should($repository, $method . ' uses ('.implode(',', $arguments).') and returns {return}', $newParameters);
                    } else {
                        $this->should($repository, $method . ' returns {return}', $parameter);
                    }
                }
            }
            $this->should($entityManager, 'getRepository uses ("'.$name.'") and returns {repository}', array('repository' => $repository));
        }
       
        // Define session

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\Session', array(), array(), '', false);
        foreach($sessionValues as $name => $value) {
            $this->should($session, 'get uses ("'.$name.'") and returns {value}', array('value' => $value));
        }

        // Define logged in user
        if($loggedInUser !== null) {
            try {
                $repository = $entityManager->getRepository("CodeOtter\Account\Repository\AccountRepository");
            } catch(\Exception $exception) {
                $repository = $this->getMock('CodeOtter\Account\Repository\AccountRepository',  array(), array(), '', false);
            }

            $this->should($repository, 'getUserById uses (1) and returns {account}', array('account' => $loggedInUser));
            $this->should($entityManager, 'getRepository uses ("CodeOtter\Account\Repository\AccountRepository") and returns {repository}', array('repository' => $repository));
            $this->should($session, 'get uses ("accountId") and returns 1');
        }

        // Define Context
        $context = $this->getMock('Verband\Framework\Structure\Context', array(), array(), '', false);
        $this->should($context, 'getState uses ("framework") and returns {framework}',         array('framework' => $framework));
        $this->should($context, 'getState uses ("entityManager") and returns {entityManager}', array('entityManager' => $entityManager));
        $this->should($context, 'getState uses ("session") and returns {session}',             array('session' => $session));

        foreach($states as $name => $value) {
            $this->should($context, 'getState uses ("'.$name.'") and returns {value}',         array('value' => $value));
        }

        self::$subject = new Subject($context);
    }
}