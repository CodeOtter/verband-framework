<?php

namespace Verband\Framework\Test;

use Verband\Framework\Structure\Context;
use Verband\Framework\Structure\Subject;
/**
 * Base testcase class for all Doctrine testcases.
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
}